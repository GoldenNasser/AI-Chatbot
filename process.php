<?php
// ============================================================
// process.php — يستقبل النص من app.js ويستدعي Gemini API بأمان
// (اسمه "process.php" وليس "chat.php" لأن كلمة chat تسبب مشاكل
//  مع بعض قواعد الحماية في استضافات مجانية مثل InfinityFree)
// يجب أن يكون هذا الملف في نفس مجلد config.php و index.html و app.js
// ============================================================

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config.php';

// اسمح فقط بطلبات POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'الطريقة غير مسموحة'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$prompt = isset($input['prompt']) ? trim($input['prompt']) : '';

if ($prompt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'الرجاء إرسال نص صالح في الحقل prompt'], JSON_UNESCAPED_UNICODE);
    exit;
}

// تحقق حقيقي من وجود المفتاح — الشرط القديم كان يقارن بنص
// ("ضع_مفتاحك_هنا") غير موجود أصلًا في config.php، فكان لا يكتشف
// حالة المفتاح الفارغ الفعلية أبدًا.
if (!defined('GEMINI_API_KEY') || trim(GEMINI_API_KEY) === '') {
    http_response_code(500);
    echo json_encode([
        'error' => 'لم يتم ضبط مفتاح Gemini في config.php بعد. افتح config.php وضع مفتاحك بين علامتي التنصيص.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// gemini-2.0-flash توقف نهائيًا من جوجل في يونيو 2026.
// gemini-3.6-flash هو الموديل الحالي (صدر 21 يوليو 2026) وله باقة مجانية عبر AI Studio.
$model = 'gemini-3.6-flash';
$url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

$body = json_encode([
    'contents' => [
        ['parts' => [['text' => $prompt]]],
    ],
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . GEMINI_API_KEY, // الطريقة الحالية الموصى بها من جوجل لتمرير المفتاح
    ],
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

// بعض حسابات InfinityFree المجانية تُظهر خطأ SSL (cURL error 60).
// إذا واجهت هذا الخطأ فعّل السطرين التاليين مؤقتًا (غير موصى به على المدى الطويل):
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'فشل الاتصال بـ Gemini API: ' . $curlErr], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($response, true);

if ($httpCode >= 400) {
    http_response_code(502);
    // نعرض رسالة جوجل الحقيقية (سبب الرفض الفعلي) بدل رسالة عامة لا تفيد في التشخيص
    $googleMsg = $data['error']['message'] ?? 'لا توجد تفاصيل إضافية من جوجل.';
    echo json_encode([
        'error' => "رفض Gemini API الطلب (HTTP {$httpCode}): {$googleMsg}"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'تعذر الحصول على رد من Gemini.';

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
