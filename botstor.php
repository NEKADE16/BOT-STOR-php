<?php

require 'vendor/autoload.php';

use Telegram\Bot\Api;

$telegram = new Api('6422132151:AAEdHQgTq9Zxd8eQwOv1Vb0lMeyttnT9gfc');

$update = $telegram->getWebhookUpdates();

$message = $update->getMessage();
$chatId = $message->getChat()->getId();
$text = $message->getText();

// توصيل قاعدة البيانات
$mysqli = new mysqli("localhost", "username", "password", "database_name");

// التأكد من عدم وجود أخطاء في الاتصال بقاعدة البيانات
if ($mysqli->connect_errno) {
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => 'حدثت مشكلة في الاتصال بقاعدة البيانات.'
    ]);
    exit();
}

if ($text === '/start') {
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => 'مرحبًا بك في متجرنا!'
    ]);

    // استعلام SQL لاستعراض المنتجات
    $sql = "SELECT * FROM products";
    $result = $mysqli->query($sql);

    // التحقق من وجود نتائج
    if ($result->num_rows > 0) {
        // عرض المنتجات للمستخدمين
        while ($row = $result->fetch_assoc()) {
            $productId = $row["id"];
            $productName = $row["name"];
            $productDescription = $row["description"];
            $productPrice = $row["price"];

            // إرسال المنتجات كرسالة إلى المستخدم
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "اسم المنتج: $productName\nوصف المنتج: $productDescription\nالسعر: $productPrice"
            ]);
        }
    } else {
        // رسالة في حالة عدم وجود منتجات
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "لا توجد منتجات متاحة حاليًا."
        ]);
    }
} elseif (strpos($text, '/add_product') === 0) {
    // تقسيم الرسالة إلى قسمين: /add_product وبيانات المنتج
    $productData = explode(" ", $text, 3);

    // التحقق من توافر بيانات المنتج
    if (count($productData) != 3) {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'الرجاء تقديم بيانات المنتج بالصيغة الصحيحة.'
        ]);
        exit();
    }

    $productName = $productData[1];
    $productPrice = $productData[2];

    // إضافة المنتج إلى قاعدة البيانات
    $sql = "INSERT INTO products (name, price) VALUES ('$productName', '$productPrice')";
    if ($mysqli->query($sql) === TRUE) {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'تمت إضافة المنتج بنجاح.'
        ]);
    } else {
        $telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'حدثت مشكلة أثناء إضافة المنتج. الرجاء المحاولة مرة أخرى.'
        ]);
    }
} else {
    $telegram->sendMessage([
        'chat_id' => $chatId,
        'text' => 'آسف، لم أتمكن من فهم طلبك.'
    ]);
}

$mysqli->close();