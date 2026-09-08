<?php

session_start();
$current_page = 'home';

require_once __DIR__ . '/auth/auth.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/helpers/helpers.php';
require_once __DIR__ . '/helpers/stuff.php';

require __DIR__ . '/includes/header.php';

// Products remain database-driven; the redesign only changes presentation.
$stmt = $conn->prepare(
    "SELECT p.*,
            COALESCE(SUM(CASE WHEN o.status = 'completed' THEN oi.quantity ELSE 0 END), 0) AS units_sold
     FROM products p
     LEFT JOIN order_items oi ON oi.product_id = p.id
     LEFT JOIN orders o ON o.id = oi.order_id
     WHERE p.is_featured = 1
     GROUP BY p.id
     ORDER BY p.created_at DESC"
);
$stmt->execute();
$collection_products = $stmt->fetchAll();
$daily_popular_ids = get_daily_popular_product_ids($conn, DAILY_POPULAR_LIMIT);
?>

<!-- =========================================================
     HERO // APPROVED HOMEPAGE DESIGN
========================================================== -->
<section class="hero" id="home">
  <div class="hero-bg" aria-hidden="true">
    <img src="images/homepagebg.png" alt="" class="hero-bg-img" data-fallback="hero">
    <div class="hero-overlay"></div>
  </div>

  <div class="hero-decor hero-decor-left" aria-hidden="true"></div>
  <div class="hero-decor hero-decor-right" aria-hidden="true"></div>

  <div class="container hero-content">
    <div class="hero-meta-row">
      <p class="section-index">CI_CORE_METRICS // 01</p>
      <p class="hero-coordinate">[ GRID_14.5995N // 120.9842E ]</p>
    </div>

    <p class="tech-label">// CABALLERO INDUSTRIES</p>

    <h1 class="hero-heading">
      BUILT FOR<br>
      <span>PURPOSE.</span><br>
      DRIVEN BY<br>
      <span>INNOVATION.</span>
    </h1>

    <p class="hero-text">
      High-performance tactical apparel designed to survive the harshest environments.
      Fusing military-grade durability with futuristic aesthetic and advanced utility.
    </p>

    <a href="#gallery" class="btn btn-primary">
      EXPLORE NOW <span class="arrow">→</span>
    </a>
  </div>
</section>

<!-- =========================================================
     BRAND ETHOS // APPROVED 50/50 COMPOSITION
========================================================== -->
<section class="ethos" id="about">
  <div class="container">
    <div class="section-rail">
      <span></span>
      <p>BRAND_ETHOS // FIELD_NOTE_01</p>
    </div>

    <div class="ethos-grid">
      <div class="ethos-text">
        <p class="tech-label">// BRAND ETHOS</p>
        <h2 class="section-heading">TACTICAL.<br>FUNCTIONAL.<br>FUTURISTIC.</h2>

        <p class="body-text">
          At Caballero Industries, we build gear for the vanguard. Our products are engineered
          using advanced tech-fabrics, modular attachment systems, and weatherproofing designed
          to withstand both urban sprawls and rugged terrains. We believe outerwear shouldn't
          just look protective — it must perform flawlessly.
        </p>

        <a href="#gallery" class="text-link">
          LEARN MORE <span class="arrow">→</span>
        </a>
      </div>

      <figure class="ethos-image">
        <div class="image-frame-corner image-frame-corner-a" aria-hidden="true"></div>
        <div class="image-frame-corner image-frame-corner-b" aria-hidden="true"></div>
        <img
          src="images/tactical jacket.png"
          alt="Caballero Industries black tactical outerwear"
          data-fallback="product"
        >
        <figcaption>CI_APPAREL_TEST // OUTERWEAR_01</figcaption>
      </figure>
    </div>
  </div>
</section>

<!-- =========================================================
     STRATEGIC PRINCIPLES // 02
========================================================== -->
<section class="principles">
  <div class="container">
    <div class="section-rail">
      <span></span>
      <p>OPERATIONAL_VALUES // 02</p>
    </div>

    <div class="section-intro section-intro-centered">
      <p class="tech-label">// STRATEGIC PRINCIPLES</p>
      <h2 class="section-heading">BUILD DIFFERENT. CREATE IMPACT.</h2>
    </div>

    <div class="principles-grid">
      <?php foreach ($principles as $index => $principle) : ?>
        <article class="principle-card">
          <div class="principle-topline">
            <span class="principle-number">
              <?php echo str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); ?>
            </span>
            <span class="principle-indicator" aria-hidden="true"></span>
          </div>

          <h3 class="principle-title"><?php echo safe_output($principle['title']); ?></h3>
          <p class="principle-text"><?php echo safe_output($principle['text']); ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========================================================
     FEATURED COLLECTION // 03
========================================================== -->
<section class="collection" id="gallery">
  <div class="container">
    <div class="section-rail">
      <span></span>
      <p>READY_RESERVES // 03</p>
    </div>

    <div class="section-intro section-intro-split">
      <div>
        <p class="tech-label">// SPEC_OPS_GEAR</p>
        <h2 class="section-heading">FEATURED COLLECTION</h2>
      </div>

      <p class="collection-meta">
        DEPT_LIST_V25 // <?php echo count($collection_products); ?> PIECES LOADED
      </p>
    </div>

    <div class="system-strip" aria-hidden="true">
      <div class="system-strip-track">
        <span>SYS.ACTIVE</span><span>SYS.ACTIVE</span><span>SYS.ACTIVE</span><span>SYS.ACTIVE</span>
        <span>SYS.ACTIVE</span><span>SYS.ACTIVE</span><span>SYS.ACTIVE</span><span>SYS.ACTIVE</span>
      </div>
    </div>

    <?php if (empty($collection_products)) : ?>
      <p class="body-text">No products yet — add one from the admin panel.</p>
    <?php else : ?>
      <div class="product-grid">
        <?php foreach ($collection_products as $product) : ?>
          <a href="product.php?id=<?php echo (int) $product['id']; ?>" class="product-card">
            <div class="product-image">
              <div class="product-tags">
                <?php if (in_array((int) $product['id'], $daily_popular_ids, true)) : ?>
                  <span class="slant-tag slant-tag-popular"><span>POPULAR</span></span>
                <?php endif; ?>
                <?php if ($product['discount_price']) : ?>
                  <?php $discount_percent = (int) round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>
                  <span class="slant-tag slant-tag-sale"><span>-<?php echo $discount_percent; ?>%</span></span>
                <?php endif; ?>
              </div>
              <?php if ((int) $product['quantity'] === 0) : ?>
                <span class="product-tag product-tag-soldout">SOLD OUT</span>
              <?php endif; ?>

              <img
                src="<?php echo safe_output(asset_url($product['image'])); ?>"
                alt="<?php echo safe_output($product['name']); ?>"
                data-fallback="product"
              >

              <span class="product-corner" aria-hidden="true">CI</span>
            </div>

            <div class="product-info">
              <p class="product-code"><?php echo safe_output($product['product_code']); ?></p>
              <h3 class="product-name"><?php echo safe_output($product['name']); ?></h3>

              <?php if ($product['discount_price']) : ?>
                <p class="product-price">
                  <span class="price-old"><?php echo format_price($product['price']); ?></span>
                  <?php echo format_price($product['discount_price']); ?>
                </p>
              <?php else : ?>
                <p class="product-price"><?php echo format_price($product['price']); ?></p>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- =========================================================
     VANGUARD BROADCAST // 04
========================================================== -->
<section class="feature-cta">
  <div class="feature-cta-bg" aria-hidden="true">
    <img src="images/homepagebg.png" alt="" data-fallback="hero">
    <div class="feature-cta-overlay"></div>
  </div>

  <div class="container">
    <div class="section-rail section-rail-on-image">
      <span></span>
      <p>VANGUARD_BROADCAST // 04</p>
    </div>

    <div class="feature-cta-content">
      <p class="tech-label">// MANDATE_05</p>
      <h2 class="feature-cta-heading">DESIGNED FOR THOSE<br>WHO MOVE DIFFERENT.</h2>
      <a href="#gallery" class="btn btn-primary">
        VIEW COLLECTION <span class="arrow">→</span>
      </a>
    </div>
  </div>
</section>

<!-- =========================================================
     VALIDATED INTEGRATIONS // 05
========================================================== -->
<section class="values">
  <div class="container">
    <div class="section-rail">
      <span></span>
      <p>SYNDICATE_ALLIES // 05</p>
    </div>

    <div class="section-intro section-intro-centered">
      <p class="tech-label">// VALIDATED_INTEGRATIONS</p>
      <h2 class="section-heading">TRUSTED. TESTED. PROVEN.</h2>
    </div>

    <div class="values-grid">
      <?php foreach ($partners as $partner) : ?>
        <div class="value-block">
          <h3 class="value-title"><?php echo safe_output($partner['name']); ?></h3>
          <p class="value-text"><?php echo safe_output($partner['tag']); ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- =========================================================
     TERMINAL COMMAND // 06
========================================================== -->
<section class="final-cta" id="contact">
  <div class="container">
    <div class="section-rail">
      <span></span>
      <p>TERMINAL_COMMAND // 06</p>
    </div>

    <div class="final-cta-content">
      <p class="tech-label">// INITIALIZE_DEPLOYMENT</p>
      <h2 class="final-cta-heading">BUILD DIFFERENT. CREATE<br>IMPACT.</h2>
      <a href="#gallery" class="btn btn-primary">
        EXPLORE COLLECTION <span class="arrow">→</span>
      </a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
