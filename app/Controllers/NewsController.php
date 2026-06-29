<?php

/**
 * NewsController
 * Handles frontend news listing, detail page, and comment submission (Part #4)
 */
class NewsController extends BaseController
{
    private $newsModel;
    private $commentModel;

    public function __construct()
    {
        $this->newsModel    = new News();
        $this->commentModel = new Comment();
    }

    /**
     * GET /news  — news listing with search + pagination
     */
    public function index()
    {
        $user   = Auth::check() ? Auth::user() : null;
        $search = trim($_GET['search'] ?? '');
        $page   = max(1, (int)($_GET['page'] ?? 1));

        $total      = $this->newsModel->countPublished($search);
        $newsList   = $this->newsModel->getPublished($page, $search);
        $perPage    = $this->newsModel->getPerPage();
        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        $dataModel = new Data();
        $company = $dataModel->site_content_all();

        $this->view('news/index', [
            'user'        => $user,
            'newsList'    => $newsList,
            'search'      => $search,
            'currentPage' => $page,
            'company'     => $company,
            'totalPages'  => $totalPages,
            'total'       => $total,
            'extraCss' => [
                'assets/css/news.css'
            ]
        ]);
    }

    /**
     * GET /news/detail/{slug}  — single news detail + comments
     */
    public function detail($slug = null)
    {
        if (!$slug) {
            $this->redirect('news');
            return;
        }

        $user = Auth::check() ? Auth::user() : null;
        $news = $this->newsModel->getBySlug($slug);
        $dataModel = new Data();
        $company = $dataModel->site_content_all();

        if (!$news) {
            // Article not found — show listing with error message
            $this->view('news/index', [
                'user'        => $user,
                'newsList'    => [],
                'search'      => '',
                'currentPage' => 1,
                'totalPages'  => 1,
                'total'       => 0,
                'pageError'   => 'Bài viết không tồn tại hoặc đã bị gỡ xuống!',
            ]);
            return;
        }

        $related      = $this->newsModel->getRelated($news['id'], $news['tags'] ?? '');
        $comments     = $this->commentModel->getByNewsId($news['id']);
        $commentCount = $this->commentModel->countByNewsId($news['id']);

        // Flash messages from session (set after redirect)
        $commentError   = $_SESSION['comment_error']   ?? null;
        $commentSuccess = $_SESSION['comment_success'] ?? null;
        unset($_SESSION['comment_error'], $_SESSION['comment_success']);

        $this->view('news/detail', [
            'user'           => $user,
            'news'           => $news,
            'related'        => $related,
            'comments'       => $comments,
            'commentCount'   => $commentCount,
            'company'        => $company,
            'commentError'   => $commentError,
            'commentSuccess' => $commentSuccess,
            'extraCss' => [
                'assets/css/news.css'
            ]
        ]);
    }

    /**
     * POST /news/comment_post  — submit a comment (requires login)
     */
    public function comment_post()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('news');
            return;
        }

        $slug   = trim($_POST['slug']    ?? '');
        $newsId = (int)($_POST['news_id'] ?? 0);

        // Must be logged in
        if (!Auth::check()) {
            $_SESSION['comment_error'] = 'Bạn cần đăng nhập để bình luận!';
            $this->redirect('news/detail/' . $slug . '#comments');
            return;
        }

        $content = trim($_POST['content'] ?? '');

        // Server-side validation
        if (empty($content)) {
            $_SESSION['comment_error'] = 'Nội dung bình luận không được để trống!';
            $this->redirect('news/detail/' . $slug . '#comments');
            return;
        }
        if (mb_strlen($content) < 5) {
            $_SESSION['comment_error'] = 'Bình luận phải có ít nhất 5 ký tự!';
            $this->redirect('news/detail/' . $slug . '#comments');
            return;
        }
        if (mb_strlen($content) > 1000) {
            $_SESSION['comment_error'] = 'Bình luận không được vượt quá 1000 ký tự!';
            $this->redirect('news/detail/' . $slug . '#comments');
            return;
        }

        // XSS protection — strip any HTML tags, encode special chars
        $content = htmlspecialchars(strip_tags($content), ENT_QUOTES, 'UTF-8');

        $ok = $this->commentModel->create([
            'user_id'   => Auth::id(),
            'target_id' => $newsId,
            'content'   => $content,
        ]);

        if ($ok) {
            $_SESSION['comment_success'] = 'Bình luận của bạn đã được gửi và đang chờ duyệt. Cảm ơn bạn!';
        } else {
            $_SESSION['comment_error'] = 'Có lỗi xảy ra, vui lòng thử lại!';
        }

        $this->redirect('news/detail/' . $slug . '#comments');
    }
}