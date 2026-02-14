# ใช้ PHP เวอร์ชั่น 8.2 พร้อม Apache Web Server
FROM php:8.2-apache

# ติดตั้ง Extension ที่จำเป็น (mysqli สำหรับต่อ Database)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# เปิดใช้งาน mod_rewrite (เผื่อต้องใช้ .htaccess)
RUN a2enmod rewrite

# ก๊อปปี้ไฟล์โค้ดทั้งหมดในโฟลเดอร์ปัจจุบัน ไปใส่ใน Server
COPY . /var/www/html/

# อนุญาตให้ Render เข้าถึง Port 80
EXPOSE 80