<?php require BASE_PATH . '/app/Views/partials/header.php'; ?>

<?php
// Lấy các tham số từ URL để duy trì trạng thái lọc
$currentCategory = $_GET['category'] ?? 'all';
$currentSort = $_GET['sort'] ?? 'newest';
$searchKeyword = $_GET['search'] ?? '';

// Hàm tạo URL để không làm mất các tham số khác khi nhấn vào lọc/phân trang
function buildUrl($overrides = [])
{
    $params = array_merge($_GET, $overrides);
    return "?" . http_build_query($params);
}
?>

<main class="site-main" style="padding-top: 0;">

    <section class="page-hero" style="padding: 120px 0 60px 0; background: linear-gradient(135deg, rgba(18, 56, 42, 0.9), rgba(45, 138, 95, 0.8)), url('<?= BASE_URL ?>/file/render?path=uploads/images/shop-hero-img.jpg') center/cover;">
        <div class="container text-center" data-aos="fade-up">
            <h1 class="display-4 fw-bold text-white"><?= e(content_value('shop.hero_title', 'Cửa Hàng Xanh')) ?></h1>
            <p class="mx-auto text-white opacity-75" style="max-width: 600px;">
                <?= e(content_value('shop.hero_description', 'Khám phá bộ sưu tập cây xanh...')) ?>
            </p>

            <!-- <div class="mt-4 mx-auto" style="max-width: 500px;">
                <form action="" method="GET" class="input-group shadow-lg rounded-pill overflow-hidden">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">

                    <input type="text" name="search" class="form-control border-0 ps-4 py-3"
                        placeholder="<?= e(content_value('shop.search_placeholder', 'Tìm kiếm cây bạn yêu thích...')) ?>"
                        value="<?= htmlspecialchars($searchKeyword) ?>">
                    <button class="btn btn-success px-4" type="submit">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>
            </div> -->
        </div>
    </section>

    <section id="product-list" class="section-padding bg-soft">
        <div class="container">

            <div class="row mb-4 align-items-center">
                <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/shop?category=all" class="btn <?= $currentCategory === 'all' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Tất cả</a>
                        <a href="<?= BASE_URL ?>/shop?category=Để bàn" class="btn <?= $currentCategory === 'Để bàn' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Để bàn</a>
                        <a href="<?= BASE_URL ?>/shop?category=Sàn nhà" class="btn <?= $currentCategory === 'Sàn nhà' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Sàn nhà</a>
                        <a href="<?= BASE_URL ?>/shop?category=Phụ kiện" class="btn <?= $currentCategory === 'Phụ kiện' ? 'btn-success' : 'btn-outline-success' ?> rounded-pill px-3">Phụ kiện</a>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3 mb-md-0">
                    <form action="<?= BASE_URL ?>/shop" method="GET" class="d-flex border border-success rounded-pill overflow-hidden bg-white">
                        <?php if ($currentCategory !== 'all'): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                        <?php endif; ?>
                        <?php if ($currentSort !== 'newest'): ?>
                            <input type="hidden" name="sort" value="<?= htmlspecialchars($currentSort) ?>">
                        <?php endif; ?>

                        <input type="text" name="search" class="form-control border-0 ps-3 py-2 shadow-none text-sm"
                            placeholder="<?= e(content_value('shop.search_placeholder', 'Tìm kiếm cây...')) ?>"
                            value="<?= htmlspecialchars($searchKeyword) ?>">
                        <button type="submit" class="btn btn-success px-3"><i class="fa-solid fa-magnifying-glass"></i></button>
                    </form>
                </div>

                <div class="col-lg-3 col-md-6 text-md-end">
                    <form action="<?= BASE_URL ?>/shop" method="GET" class="d-inline-block w-100">
                        <?php if ($currentCategory !== 'all'): ?>
                            <input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory) ?>">
                        <?php endif; ?>
                        <?php if ($searchKeyword !== ''): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($searchKeyword) ?>">
                        <?php endif; ?>

                        <span class="text-muted d-none d-xxl-inline me-2"><?= e(content_value('shop.sort_label', 'Sắp xếp:')) ?></span>
                        <select name="sort" class="form-select d-inline-block w-auto border-success rounded-pill" onchange="this.form.submit()">
                            <option value="newest" <?= $currentSort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                            <option value="price_asc" <?= $currentSort === 'price_asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                            <option value="price_desc" <?= $currentSort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                        </select>
                    </form>
                </div>
            </div>

            <?php if ($searchKeyword): ?>
                <div class="mb-4" data-aos="fade-in">
                    <h5 class="text-muted">
                        Kết quả tìm kiếm cho: <span class="text-success">"<?= htmlspecialchars($searchKeyword) ?>"</span>
                        <a href="?" class="ms-2 btn btn-sm btn-light rounded-pill">Xóa tìm kiếm</a>
                    </h5>
                </div>
            <?php endif; ?>

            <?php if (!empty($products)): ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up">
                            <div class="product-card h-100 bg-white border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="position-relative">
                                    <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>">
                                        <img src="<?= strpos($product['image'], 'http') === 0 ? $product['image'] : BASE_URL . '/' . ltrim($product['image'], '/') ?>"
                                            alt="<?= $product['name'] ?>"
                                            class="w-100 object-fit-cover"
                                            style="height: 240px;">
                                    </a>
                                </div>

                                <div class="p-3 text-center">
                                    <span class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 1px;"><?= $product['category'] ?></span>
                                    <h3 class="fs-6 mt-1 mb-2">
                                        <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>" class="text-decoration-none fw-bold" style="color: var(--green-900);">
                                            <?= $product['name'] ?>
                                        </a>
                                    </h3>

                                    <div class="fw-bold mb-3" style="color: var(--green-700); font-size: 1.1rem;">
                                        <?= isset($product['price']) ? number_format($product['price'], 0, ',', '.') . 'đ' : 'Liên hệ' ?>
                                    </div>

                                    <div class="d-grid">
                                        <a href="<?= BASE_URL ?>/shop/detail/<?= $product['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill">
                                            <i class="fa-solid fa-eye me-1"></i> Chi tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-center mt-5" data-aos="fade-up">
                        <nav aria-label="Điều hướng phân trang">
                            <ul class="pagination pagination-modern mb-0">
                                <li class="page-item <?= ($currentPage <= 1) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= buildUrl(['page' => $currentPage - 1]) ?>" aria-label="Trang trước">
                                        <i class="fa-solid fa-chevron-left fa-sm"></i>
                                    </a>
                                </li>

                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= buildUrl(['page' => $i]) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= ($currentPage >= $totalPages) ? 'disabled' : '' ?>">
                                    <a class="page-link" href="<?= buildUrl(['page' => $currentPage + 1]) ?>" aria-label="Trang sau">
                                        <i class="fa-solid fa-chevron-right fa-sm"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="text-center py-5">
                    <img src="<?= BASE_URL ?>/file/render?path=uploads/images/shop-search.png" width="100" alt="Not found" class="opacity-50 mb-3">
                    <h3 class="text-muted"><?= e(content_value('shop.empty_title', 'Không tìm thấy cây nào phù hợp')) ?></h3>
                    <p><?= e(content_value('shop.empty_text', 'Vui lòng thử từ khóa khác hoặc xóa bộ lọc.')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<style>
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }

    .pagination-rounded .page-link {
        border-radius: 50% !important;
        margin: 0 3px;
        border: none;
        color: #198754;
    }

    .pagination-rounded .page-item.active .page-link {
        background-color: #198754;
        color: white;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (window.location.search) {
            const productList = document.getElementById('product-list');
            if (productList) {
                const headerOffset = 0;
                const elementPosition = productList.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                // setTimeout nhẹ để đảm bảo DOM đã render xong hoàn toàn
                setTimeout(() => {
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: "smooth"
                    });
                }, 100);
            }
        }
    });
</script>

<?php require BASE_PATH . '/app/Views/partials/footer.php'; ?>