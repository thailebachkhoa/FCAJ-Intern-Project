<?php

class ContactController extends BaseController
{
    public function index()
    {
        $user = Auth::check() ? Auth::user() : null;

        // Flash messages
        $success = $_SESSION['contact_success'] ?? null;
        $error   = $_SESSION['contact_error']   ?? null;
        unset($_SESSION['contact_success'], $_SESSION['contact_error']);

        $this->view('pages/contact', [
            'user'    => $user,
            'success' => $success,
            'error'   => $error,
        ]);
    }

    public function submit()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('contact');
            return;
        }

        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Server-side validation
        if (empty($name) || mb_strlen($name) < 2) {
            $_SESSION['contact_error'] = 'Vui lòng nhập họ tên (ít nhất 2 ký tự)!';
            $this->redirect('contact');
            return;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['contact_error'] = 'Email không hợp lệ!';
            $this->redirect('contact');
            return;
        }
        if (empty($subject)) {
            $_SESSION['contact_error'] = 'Vui lòng chọn chủ đề!';
            $this->redirect('contact');
            return;
        }
        if (empty($message) || mb_strlen($message) < 10) {
            $_SESSION['contact_error'] = 'Nội dung tin nhắn phải có ít nhất 10 ký tự!';
            $this->redirect('contact');
            return;
        }
        if (mb_strlen($message) > 2000) {
            $_SESSION['contact_error'] = 'Nội dung không được vượt quá 2000 ký tự!';
            $this->redirect('contact');
            return;
        }

        // Sanitize
        $name    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
        $email   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars(strip_tags($message), ENT_QUOTES, 'UTF-8');

        // Insert vào DB
        try {
            $db = Database::getInstance();
            $db->query("INSERT INTO contacts (name, email, subject, message, is_read, created_at)
                        VALUES (:name, :email, :subject, :message, 0, NOW())");
            $db->bind(':name',    $name);
            $db->bind(':email',   $email);
            $db->bind(':subject', $subject);
            $db->bind(':message', $message);
            $db->execute();

            $_SESSION['contact_success'] = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong vòng 24 giờ.';
        } catch (Exception $e) {
            $_SESSION['contact_error'] = 'Có lỗi xảy ra, vui lòng thử lại!';
        }

        $this->redirect('contact');
    }
}