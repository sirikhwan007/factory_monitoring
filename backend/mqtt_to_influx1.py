import os
import json
import paho.mqtt.client as mqtt
from influxdb_client import InfluxDBClient, Point
from influxdb_client.client.write_api import SYNCHRONOUS

# ===== CONFIG (ดึงจาก Environment Variables) =====
# บน Render ให้ไปตั้งค่าที่ Environment -> Add Environment Variable
# ===== CONFIG (Environment Variables) =====
INFLUXDB_URL = os.getenv("INFLUX_URL")
INFLUXDB_TOKEN = os.getenv("INFLUX_TOKEN")
INFLUXDB_ORG = os.getenv("INFLUX_ORG")
INFLUXDB_BUCKET = os.getenv("INFLUX_BUCKET")

MQTT_BROKER = os.getenv("MQTT_URL")        # เช่น mqtt://broker.hivemq.com
MQTT_USER = os.getenv("MQTT_USER")
MQTT_PASS = os.getenv("MQTT_PASS")

# ===== InfluxDB Client =====
client_influx = InfluxDBClient(url=INFLUXDB_URL, token=INFLUXDB_TOKEN, org=INFLUXDB_ORG)
write_api = client_influx.write_api(write_options=SYNCHRONOUS)

# ===== MQTT CALLBACKS =====
def on_connect(client, userdata, flags, rc):
    
    if rc == 0:
        print("✅ Connected to MQTT Broker")
        client.subscribe("test/sensor/data")
    else:
        print(f"❌ MQTT Connection failed (RC: {rc})")

def on_message(client, userdata, msg):
    try:
        payload = json.loads(msg.payload.decode())
        mac = payload.get("mac", "unknown")
        points = []

        # --- Temperature ---
        temp = payload.get("temperature")
        if isinstance(temp, (int, float)):
            points.append(Point("DS18B20").tag("device", mac).field("temperature", float(temp)))

        # --- PZEM ---
        pzem = payload.get("pzem", {})
        if isinstance(pzem, dict):
            p = Point("PZEM004T").tag("device", mac)
            valid = False
            for k in ["voltage","current","power","energy","frequency","power_factor"]:
                v = pzem.get(k)
                if isinstance(v, (int, float)):
                    p.field(k, float(v))
                    valid = True
            if valid: points.append(p)

        # --- MPU ---
        vib = payload.get("accel_percent")
        if isinstance(vib, (int, float)):
            points.append(Point("MPU6050").tag("device", mac).field("accel_percent", float(vib)))

        # --- Write Data ---
        if points:
            write_api.write(bucket=INFLUXDB_BUCKET, org=INFLUXDB_ORG, record=points)
            print(f"InfluxDB: Recorded {len(points)} fields for {mac}")

    except Exception as e:
        print(f"Process Error: {e}")

# ===== MAIN RUNNER =====
mqtt_client = mqtt.Client(client_id="server-bridge-worker", protocol=mqtt.MQTTv311)
mqtt_client.username_pw_set(MQTT_USER, MQTT_PASS)
mqtt_client.on_connect = on_connect
mqtt_client.on_message = on_message

# เพิ่มการเปิดใช้งาน Reconnect อัตโนมัติ
broker = MQTT_BROKER.replace("mqtt://", "").replace("tcp://", "")
mqtt_client.connect_async(broker, 1883, 60)


try:
    print("Starting Background Worker...")
    mqtt_client.loop_forever()
except KeyboardInterrupt:
    print("Stopping...")
finally:
    client_influx.close()