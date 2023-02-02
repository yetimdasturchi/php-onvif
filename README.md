
# php-onvif

Ushbu kutubxona IP kameralar uchun standartlashtirilgan onvif protokoli bilan ishlash uchun mo'ljallangan.

## Imkoniyatlar

- Media, PTZ va snapshot hazolalarni avtomatik aniqlash
- Davomiy va qadamlarga asoslantirib kordinalarni boshqarish

## Ishga tushirish

```php
include 'Onvif.php';

/* Avtorizatsiya talab qilinmaydigan holatlar uchun obyekt argumentidagi massivni bo'sh qoldiring */
$onvif = new Onvif('192.168.200.1:80', [ // Ip manzil va port
    'username' => 'admin', //Foydalanuvchi
    'password' => 'admin123',  //Parol
]);
```

## Metodlar

 - getOnvifVersion - Onvif versiyasini aniqlash
- getMediaUri - Media boshqaruv manzilini aniqlash
- getPtzUri - PTZ manzilini aniqlash
- getSources - Video oqimlar massivi
- getStreamUris - Video oqimlar havolalari massivda
- getStreamUri - Yagona video oqim manzili olish
- getSnapshotUris - Surat olish uchun havolalari massivda
- getSnapshotUri - Yagona surat olish manzili olish
- move - Davomiy harakat
- step - Qadamba-qadam harakat
- stop - Harakatni to'xtatish

## Demo

Kutubxona ishlashini avtomatik demostrasiya qiluvchi faylni ishga tushirish:

```bash
$: /usr/bin/php demo.php
```
Klaviatura orqali boshqaruvchi demo dasturni ishga tushirish:

```bash
$: /usr/bin/php keypad.php
```
---------------
Ko'proq ma'lumot olish: https://t.me/yetimdasturchi