<?php
class FaqController extends BaseController
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

        $faqs = $this->dataModel->get_faqs();


        if (!$faqs) {
            $faqs = [];
        }

        $user = Auth::check() ? Auth::user() : null;

        // Truyền $faqs sang View
        $this->view('pages/faq', [
            'user' => $user,
            'faqs' => $faqs
        ]);
    }
}