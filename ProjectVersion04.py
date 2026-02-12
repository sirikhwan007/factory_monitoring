import _thread, time, json, gc, math, network
from umqtt.simple import MQTTClient
from machine import Pin, SPI, UART, I2C, WDT
import onewire, ds18x20
from mpu6050 import accel
from st7735 import ST7735, BLACK, CYAN, GREEN, YELLOW, RED

# ==================================================
# CONFIG
# ==================================================
SSID, PASSWORD = "Galaxy A54", "0935160117"

BROKER = "broker.freemqtt.com"
PORT = 1883
CLIENT_ID = "esp32-client1"
MQTT_USER, MQTT_PASS = "freemqtt", "public"

TOPIC_PUB  = b"test/sensor/data"
TOPIC_CMD  = b"test/cmd/led"
TOPIC_LED  = b"test/state/led"

# ==================================================
# LED
# ==================================================
LED_RED    = Pin(19, Pin.OUT)
LED_YELLOW = Pin(32, Pin.OUT)
LED_GREEN  = Pin(33, Pin.OUT)

def all_led_off():
    LED_RED.off()
    LED_YELLOW.off()
    LED_GREEN.off()

all_led_off()

# ==================================================
# WIFI
# ==================================================
wifi = network.WLAN(network.STA_IF)
wifi.active(True)
wifi.connect(SSID, PASSWORD)
while not wifi.isconnected():
    time.sleep(1)

print("WiFi OK:", wifi.ifconfig()[0])
mac_addr = ':'.join('%02x' % b for b in wifi.config('mac'))

# ==================================================
# MQTT
# ==================================================
mqtt_lock = _thread.allocate_lock()

# แก้ไขฟังก์ชัน Callback
def mqtt_callback(topic, msg):
    print("ได้รับคำสั่ง:", msg)
    print("• ขนาดข้อมูล:", len(msg), "bytes")
    try:
        cmd = json.loads(msg)
        p_num = cmd.get("pin")
        p_val = cmd.get("value")
        
        if p_num is not None:
            # แปลงเป็น int เพื่อความชัวร์ และสั่งงาน
            target = Pin(int(p_num), Pin.OUT)
            target.value(int(p_val))
            print(" สั่งงานขา:", p_num, "สถานะ:", p_val)
    except Exception as e:
        print(" Error ใน Callback:", e)

def mqtt_connect():
    global client
    # เพิ่ม keepalive=60 เพื่อให้การเชื่อมต่อไม่หลุดง่าย
    client = MQTTClient(CLIENT_ID, BROKER, user=MQTT_USER, password=MQTT_PASS, keepalive=60)
    client.set_callback(mqtt_callback)
    client.connect()
    client.subscribe(TOPIC_CMD)
    print("✅ MQTT CONNECTED TO:", BROKER)

mqtt_connect()

# ==================================================
# WDT
# ==================================================
wdt = WDT(timeout=20000)

# ==================================================
# TFT
# ==================================================
spi = SPI(2, baudrate=5_000_000, sck=Pin(18), mosi=Pin(21))
tft = ST7735(spi, Pin(5), Pin(2), Pin(4), 128, 160, 3)
tft.init()
tft.fill(BLACK)

tft.text("Motor Monitoring", 15, 5, CYAN)
tft.hline(0, 18, 160, CYAN)
tft.text("V :", 5, 30, CYAN)
tft.text("I :", 5, 42, CYAN)
tft.text("P :", 5, 54, CYAN)
tft.text("E :", 5, 66, CYAN)
tft.text("TEMP:", 5, 85, CYAN)
tft.text("VIB :", 5, 100, CYAN)

def clear(x, y, w=100, h=12):
    tft.fill_rect(x, y, w, h, BLACK)

# ==================================================
# HARDWARE
# ==================================================
uart = UART(1, baudrate=9600, tx=27, rx=14, timeout=100)  # 100ms

ADDR = 0x01

ds = ds18x20.DS18X20(onewire.OneWire(Pin(26)))
roms = ds.scan()

i2c = I2C(1, scl=Pin(22), sda=Pin(23))
mpu = accel(i2c)

# ==================================================
# SHARED DATA
# ==================================================
sensor_data = {
    "pzem": {},
    "temperature": 0.0,
    "accel_percent": 0.0
}

# ==================================================
# PZEM
# ==================================================
def crc16(data):
    crc = 0xFFFF
    for b in data:
        crc ^= b
        for _ in range(8):
            crc = (crc >> 1) ^ 0xA001 if crc & 1 else crc >> 1
    return crc

def read_reg(reg, length):
    cmd = bytearray(8)
    cmd[0] = ADDR
    cmd[1] = 0x04
    cmd[2] = reg >> 8
    cmd[3] = reg & 0xFF
    cmd[4] = 0
    cmd[5] = length
    c = crc16(cmd[:6])
    cmd[6] = c & 0xFF
    cmd[7] = c >> 8

    uart.write(cmd)

    t0 = time.ticks_ms()
    while uart.any() == 0:
        if time.ticks_diff(time.ticks_ms(), t0) > 200:
            return None
        time.sleep_ms(5)

    return uart.read()


def pzem_val(data, div, is_32bit=False):
    if not data or len(data) < 5:
        return 0.0

    if is_32bit:
        if len(data) < 9: 
            return 0.0
        low_word = (data[3] << 8) | data[4]
        high_word = (data[5] << 8) | data[6]
        val = (high_word << 16) | low_word
    else:
        
        val = (data[3] << 8) | data[4]

    return val / div

def read_pzem_all():
    
    try:
        results = {
            "voltage":      pzem_val(read_reg(0x0000, 1), 10),
            "current":      pzem_val(read_reg(0x0001, 2), 1000, True),
            "power":        pzem_val(read_reg(0x0003, 2), 10, True),
            "energy":       pzem_val(read_reg(0x0005, 2), 1, True),
            "frequency":    pzem_val(read_reg(0x0007, 1), 10),
            "power_factor": pzem_val(read_reg(0x0008, 1), 100)
        }
        return results
    except Exception as e:
        print("Read Error:", e)
        return None
# ==================================================
# THREADS (TIMING OPTIMIZED)
# ==================================================
def thread_pzem():
    while True:
        sensor_data["pzem"] = read_pzem_all()
        time.sleep(5)   

def thread_temp():
    while True:
        if roms:
            ds.convert_temp()
            time.sleep_ms(750)
            sensor_data["temperature"] = ds.read_temp(roms[0])
        time.sleep(1)

def thread_mpu():
    VIB_LIMIT_G = 0.5     
    BASELINE_TIME = 15     
    SAMPLE_DELAY = 0.1     

    baseline_samples = []
    baseline_g = None
    start_time = time.time()

    while True:
        try:
            vib_g = mpu.vibration_g(samples=8)

            # ---------- Phase 1: เก็บ baseline ----------
            if baseline_g is None:
                baseline_samples.append(vib_g)

                if time.time() - start_time >= BASELINE_TIME:
                    baseline_g = sum(baseline_samples) / len(baseline_samples)
                    print("Baseline vibration =", baseline_g)
                time.sleep(SAMPLE_DELAY)
                continue

            # ---------- Phase 2: ใช้งานจริง ----------
            delta_g = abs(vib_g - baseline_g)
            vib_percent = (delta_g / VIB_LIMIT_G) * 100

            # clamp ค่า
            if vib_percent < 0:
                vib_percent = 0
            elif vib_percent > 100:
                vib_percent = 100

            sensor_data["accel_percent"] = vib_percent

        except Exception as e:
            print("MPU Error:", e)

        time.sleep(SAMPLE_DELAY)



def publish_led_state():
    state = {
        "red": LED_RED.value(),
        "yellow": LED_YELLOW.value(),
        "green": LED_GREEN.value()
    }
    with mqtt_lock:
        client.publish(TOPIC_LED, json.dumps(state))

def thread_mqtt():
    while True:
        try:
            client.check_msg()
        except:
            time.sleep(2)
            mqtt_connect()
        time.sleep(0.1)

def thread_publish():
    while True:
        payload = {
            "mac": mac_addr,
            "pzem": sensor_data["pzem"],
            "temperature": sensor_data["temperature"],
            "accel_percent": sensor_data["accel_percent"]
        }
        print("[PUB]", payload)
        with mqtt_lock:
            client.publish(TOPIC_PUB, json.dumps(payload))
        wdt.feed()
        time.sleep(5)   

# ==================================================
# START
# ==================================================
_thread.start_new_thread(thread_pzem, ())
_thread.start_new_thread(thread_temp, ())
_thread.start_new_thread(thread_mpu, ())
_thread.start_new_thread(thread_mqtt, ())
_thread.start_new_thread(thread_publish, ())

# ==================================================
# CACHE ค่าเดิม (อยู่นอก loop)
last_pzem = {
    "voltage": None,
    "current": None,
    "power": None,
    "energy": None
}
last_temp = None
last_vib  = None

# ==================================================
# MAIN LOOP (TFT)
last_tft = time.ticks_ms()
TFT_INTERVAL = 500

def changed(a, b, eps=0.01):
    if a is None or b is None:
        return True
    return abs(a-b) > eps


while True:
    try:
        if time.ticks_diff(time.ticks_ms(), last_tft) >= TFT_INTERVAL:
            last_tft = time.ticks_ms()

            p = sensor_data["pzem"] or {}

            # ---------- Voltage ----------
            v = p.get("voltage", 0)
            if changed(v, last_pzem["voltage"], 0.1):
                color = RED if v >=300 else YELLOW if v >= 250 else GREEN
                clear(40,30)
                tft.text("%.1f V" % v, 40, 30, GREEN)
                last_pzem["voltage"] = v

            # ---------- Current ----------
            i = p.get("current", 0)
            if changed(i, last_pzem["current"], 0.001):
                color = RED if i >=8 else YELLOW if i >= 5 else GREEN
                clear(40,42)
                tft.text("%.3f A" % i, 40, 42, GREEN)
                last_pzem["current"] = i

            # ---------- Power ----------
            w = p.get("power", 0)
            if changed(w, last_pzem["power"], 0.5):
                color = RED if w >=1200 else YELLOW if w >= 900 else GREEN
                clear(40,54)
                tft.text("%.1f W" % w, 40, 54, GREEN)
                last_pzem["power"] = w
                
            # ---------- Energy ----------
            e = p.get("energy", 0)
            if changed(e, last_pzem["energy"], 1):
                
                clear(40,66)
                tft.text("%.0f Wh" % e, 40, 66, GREEN)
                last_pzem["energy"] = e

            # ---------- Temperature ----------
            temp = sensor_data["temperature"]
            if changed(temp, last_temp, 0.0):
                
                clear(55,75)
                tft.text("%.2f C" % temp, 55, 85, GREEN)
                last_temp = temp

            # ---------- Vibration ----------
            vib = sensor_data["accel_percent"]
            if changed(vib, last_vib, 0.5):
                color = RED if vib >= 80 else YELLOW if vib >= 50 else GREEN
                clear(55,100)
                tft.text("%.1f %%" % vib, 55, 100, color)
                last_vib = vib

        wdt.feed()
        time.sleep(0.05)   

    except Exception as e:
        print("MAIN LOOP ERROR:", e)