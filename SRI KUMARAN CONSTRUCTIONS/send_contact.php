<?php
// send_contact.php
// Save this file in the same folder as index.html

// ---------- CONFIG ----------
$adminEmail = "ranjithsivalingam28@gmail.com"; // <-- change this to admin email
$saveFile = __DIR__ . "/submissions.json"; // file to store submissions
// ---------- END CONFIG ------

function load_submissions($file) {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

function save_submissions($file, $arr) {
    file_put_contents($file, json_encode($arr, JSON_PRETTY_PRINT));
}

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

// collect and sanitize
$name = isset($_POST['name']) ? trim(strip_tags($_POST['name'])) : '';
$email = isset($_POST['email']) ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
$phone = isset($_POST['phone']) ? trim(strip_tags($_POST['phone'])) : '';
$message = isset($_POST['message']) ? trim(strip_tags($_POST['message'])) : '';
$source = isset($_POST['source']) ? trim(strip_tags($_POST['source'])) : 'Website Form';

// minimal validation
if (empty($name) || empty($email) || empty($message)) {
    echo "<script>alert('Please fill required fields (name, email, message).'); window.history.back();</script>";
    exit;
}

// prepare submission object
$submission = [
    'id' => uniqid('', true),
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
    'source' => $source,
    'status' => 'pending',
    'created_at' => date('c')
];

// save to submissions.json
$all = load_submissions($saveFile);
array_unshift($all, $submission); // newest first
save_submissions($saveFile, $all);

// email admin notification
$subject = "New submission from {$source} — Pending approval";
$body = "New submission waiting for approval:\n\n";
$body .= "Name: {$name}\nEmail: {$email}\nPhone: {$phone}\nSource: {$source}\n\nMessage:\n{$message}\n\n";
$body .= "Approve URL (if hosting supports PHP): " . (isset($_SERVER['HTTP_HOST']) ? "https://{$_SERVER['HTTP_HOST']}/admin.php" : "admin.php") . "\n";

$headers = "From: {$email}\r\nReply-To: {$email}\r\n";

// Try to send mail (may fail on local dev)
@mail($adminEmail, $subject, $body, $headers);

// Redirect back with thank-you
echo "<script>alert('Thank you! Your message has been sent to admin for approval.'); window.location.href='index.html';</script>";
exit;
