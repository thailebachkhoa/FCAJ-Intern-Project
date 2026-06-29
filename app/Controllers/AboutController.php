<?php

/**
 * AboutController
 * Xử lý trang Giới thiệu.
 */
class AboutController extends BaseController
{
    private $db;
    private $dataModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->dataModel = new Data();
    }

    public function index()
    {
        $user = Auth::check() ? Auth::user() : null;

        // Bảng pages hiện dùng chính cho ảnh giới thiệu.
        $this->db->query("SELECT * FROM pages WHERE slug = 'about'");
        $page = $this->db->single();
        if (!$page) {
            $page = [
                'title' => 'Giới thiệu',
                'description' => '',
                'content' => 'Nội dung đang được cập nhật...',
                'image' => '',
            ];
        }

        $heroVideo = $this->dataModel->content_value('about.hero_video', 'assets/videos/about/about-hero.m3u8');
        if ($heroVideo === 'assets/videos/about/about.m3u8') {
            $heroVideo = 'assets/videos/about/about-hero.m3u8';
        }

        $this->view('pages/about', [
            'user' => $user,
            'page' => $page,
            'company' => $this->dataModel->site_content_all(),
            'heroVideo' => $heroVideo,
            'pageTitle' => $this->dataModel->content_value('about.meta_title', 'Giới thiệu | Plantify Co'),
            'pageDescription' => $this->dataModel->content_value('about.meta_description', 'Tìm hiểu Plantify Co, công ty thiết kế decor cây xanh.'),
        ]);
    }
}