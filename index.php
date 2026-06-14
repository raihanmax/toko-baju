<?php
require_once 'includes/config.php';
$pageTitle = 'Beranda';
$db = getDB();

$featured  = $db->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_featured = 1 AND p.is_active = 1 LIMIT 8");
$categories = $db->query("SELECT * FROM categories ORDER BY name");
?>
<?php include 'includes/header.php'; ?>

<!-- Banner Strip -->
<div class="banner-strip">
    Buruan Belanja &nbsp;|&nbsp;
    web: <strong>toko</strong> pakaian
</div>

<!-- HERO -->
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-text">
            <p class="hero-tag">Koleksi Baru 2026</p>
            <h1>Tampil <em>Elegan</em><br>Setiap Hari</h1>
            <p>Temukan koleksi fashion terkini — dari kasual yang santai hingga formal yang memukau.</p>
            <div class="hero-actions">
                <a href="products.php" class="btn-primary">Belanja Sekarang</a>
                <a href="products.php?category=dress" class="btn-outline">Lihat Koleksi</a>
            </div>
        </div>
        <div class="hero-image" style="position: relative; overflow: hidden;">
            <div class="hero-slider-inner" id="heroSliderTrack">
                <?php
                // Ambil gambar dari produk yang dicentang unggulan (is_featured = 1)
                $sliderQuery = $db->query("SELECT image, name, slug FROM products WHERE is_featured = 1 AND is_active = 1 ORDER BY id DESC");
                
                if ($sliderQuery && $sliderQuery->num_rows > 0):
                    while ($slide = $sliderQuery->fetch_assoc()):
                ?>
                    <div class="hero-slide-item">
                        <a href="product.php?slug=<?= $slide['slug'] ?>">
                            <?php if ($slide['image'] && file_exists(UPLOAD_DIR . $slide['image'])): ?>
                                <img src="<?= UPLOAD_URL . $slide['image'] ?>" alt="<?= htmlspecialchars($slide['name']) ?>">
                            <?php else: ?>
                                <div class="hero-image-placeholder">
                                    <div class="icon">???</div>
                                    <p class="serif" style="font-size:1.1rem;color:#888;"><?= htmlspecialchars($slide['name']) ?></p>
                                </div>
                            <?php endif; ?>
                        </a>
                    </div>
                <?php 
                    endwhile;
                else:
                    // Jika di database belum ada produk unggulan, tampilkan placeholder bawaan kamu
                ?>
                    <div class="hero-slide-item">
                        <div class="hero-image-placeholder">
                            <div class="icon">?</div>
                            <p class="serif" style="font-size:1.1rem;color:#888;">New Collection</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button class="hero-slide-nav prev" onclick="changeHeroSlide(-1)">&#10094;</button>
            <button class="hero-slide-nav next" onclick="changeHeroSlide(1)">&#10095;</button>

            <div class="hero-badge">
                <span>featured</span>
                <span class="fs-xs">product</span>
            </div>
        </div>

        <script>
        let currentHeroIndex = 0;
        const sliderTrack = document.getElementById('heroSliderTrack');

        function changeHeroSlide(direction) {
            const slides = document.querySelectorAll('.hero-slide-item');
            const totalSlides = slides.length;
            
            if (totalSlides <= 1) return;

            currentHeroIndex += direction;

            if (currentHeroIndex >= totalSlides) {
                currentHeroIndex = 0;
            }
            if (currentHeroIndex < 0) {
                currentHeroIndex = totalSlides - 1;
            }

            // Geser berdasarkan lebar dari container hero-image
            const slideWidth = sliderTrack.clientWidth;
            sliderTrack.scrollTo({
                left: currentHeroIndex * slideWidth,
                behavior: 'smooth'
            });
        }

        // Jalankan Auto Scroll otomatis setiap 4 detik
        let heroAutoTimer = setInterval(() => {
            changeHeroSlide(1);
        }, 4000);

        // Berhenti geser otomatis sementara saat mouse user menyentuh gambar
        sliderTrack.addEventListener('mouseenter', () => clearInterval(heroAutoTimer));
        sliderTrack.addEventListener('mouseleave', () => {
            heroAutoTimer = setInterval(() => {
                changeHeroSlide(1);
            }, 4000);
        });
        </script>
    </div>
</section>

<!-- FEATURES -->
<section class="section">
    <div class="container">
        <div class="features-grid">
            <div class="feature-item">
                <h4>Pengiriman Cepat</h4>
                <p>Dikirim dalam 1-3 hari kerja ke seluruh Indonesia</p>
            </div>
            <div class="feature-item">
                <h4>Mudah Dikembalikan</h4>
                <p>Tidak cocok? Kembalikan dalam 7 hari tanpa pertanyaan</p>
            </div>
            <div class="feature-item">
                <h4>Pembayaran Aman</h4>
                <p>Transfer bank & COD tersedia, 100% aman</p>
            </div>
            <div class="feature-item">
                <h4>Kualitas Terjamin</h4>
                <p>Setiap produk dicek kualitasnya sebelum dikirim</p>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="section pt-0">
    <div class="container">
        <div class="section-header">
            <div>
                <p class="section-tag">Jelajahi</p>
                <h2>Kategori Produk</h2>
            </div>
        </div>
        <div class="categories-strip">
            <a href="products.php" class="cat-chip active">Semua</a>
            <?php $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
            <a href="products.php?category=<?= $cat['slug'] ?>" class="cat-chip" data-slug="<?= $cat['slug'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="section pt-0">
    <div class="container">
        <div class="section-header">
            <div>
                <p class="section-tag">Pilihan Kami</p>
                <h2>Produk Unggulan</h2>
            </div>
            <a href="products.php" class="btn-ghost">Lihat Semua →</a>
        </div>

        <?php if ($featured->num_rows > 0): ?>
        <div class="product-grid">
            <?php while ($product = $featured->fetch_assoc()): ?>
            <a href="product.php?slug=<?= $product['slug'] ?>" class="product-card">
                <div class="product-card-img">
                    <?php if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])): ?>
                        <img src="<?= UPLOAD_URL . $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <?php else: ?>
                        <div class="img-placeholder">???</div>
                    <?php endif; ?>
                    <span class="product-badge">Unggulan</span>
                </div>
                <div class="product-card-body">
                    <p class="product-card-category"><?= htmlspecialchars($product['cat_name'] ?? '') ?></p>
                    <h3 class="product-card-name"><?= htmlspecialchars($product['name']) ?></h3>
                    <p class="product-card-price"><?= formatRupiah($product['price']) ?></p>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><p>Belum ada produk unggulan.</p></div>
        <?php endif; ?>
    </div>
</section>

<!-- PROMO BANNER -->
<div class="promo-banner">
    <div class="container">
        <p class="promo-tag">Penawaran Terbatas</p>
        <h2 class="promo-heading">Diskon Hingga <em>40%</em><br>untuk Koleksi Outerwear</h2>
        <p class="promo-sub">Terbatas untuk 50 pembeli pertama. Jangan sampai kehabisan!</p>
        <a href="products.php?category=outerwear" class="btn-accent">Lihat Promo</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
