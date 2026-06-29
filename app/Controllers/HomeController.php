<?php

/**
 * Home Controller
 * Handle homepage and public pages
 */
class HomeController extends BaseController
{

    /**
     * Homepage - visible to all (guest & members)
     */
    public function index()
    {
        $user = Auth::check() ? Auth::user() : null;
        require_once BASE_PATH . '/app/Models/Product.php';
        $productModel = new Product();
        $featuredProducts = $productModel->getFeatured();
        $this->view('pages/home', ['user' => $user, 'featuredProducts' => $featuredProducts]);
    }
}