<?php
/**
 * index.php
 * -------------------------------------------------------------
 * Homepage. Static sections (hero, ethos, principles, values) are
 * plain HTML like before. The two product areas now come straight
 * from the database, so adding a product in admin/add-product.php
 * shows up here automatically — no code changes needed.
 * -------------------------------------------------------------
 */

// require_once 'includes/auth.php';     // session + login helpers
// require_once 'database/db.php';       // $conn
// require_once 'includes/helpers.php';  // format_price(), nav_href(), etc.

session_start();

require_once 'stuff.php';            // $site_name, $nav_items, etc.
require 'includes/header.php';
?>

    <!-- =========================================================
         HERO SECTION
    ========================================================== -->
    <section class="hero" id="home">
      <div class="hero-bg">
        <img src="images/homepagebg.png" alt="" class="hero-bg-img" data-fallback="hero">
        <div class="hero-overlay"></div>
      </div>

      <div class="container hero-content">
        <p class="section-index">CI_CORE_METRICS // 01</p>
        <p class="tech-label">// CABALLERO INDUSTRIES</p>
        <h1 class="hero-heading">BUILT FOR PURPOSE.<br>DRIVEN BY INNOVATION.</h1>
        <p class="hero-text">High-performance tactical apparel designed to survive the harshest environments. Fusing military-grade durability with futuristic aesthetic and advanced utility.</p>
        <a href="#gallery" class="btn btn-primary">EXPLORE NOW <span class="arrow">→</span></a>
      </div>
    </section>

    <!-- =========================================================
         BRAND ETHOS SECTION
    ========================================================== -->
    <section class="ethos" id="about">
      <div class="container ethos-grid">

        <div class="ethos-text">
          <p class="tech-label">// BRAND ETHOS</p>
          <h2 class="section-heading">TACTICAL.<br>FUNCTIONAL.<br>FUTURISTIC.</h2>
          <p class="body-text">
            At Caballero Industries, we build gear for the vanguard. Our products are engineered
            using advanced tech-fabrics, modular attachment systems, and weatherproofing designed
            to withstand both urban sprawls and rugged terrains. We believe outerwear shouldn't
            just look protective — it must perform flawlessly.
          </p>
          <a href="#gallery" class="text-link">LEARN MORE <span class="arrow">→</span></a>
        </div>

        <div class="ethos-image">
          <img src="images/tactical jacket.png" alt="Caballero Industries black tactical hoodie" data-fallback="product">
        </div>

      </div>
    </section>

    <!-- =========================================================
         STRATEGIC PRINCIPLES
    ========================================================== -->
    <section class="principles">
      <div class="container">

        <p class="section-index">OPERATIONAL_VALUES // 02</p>

        <div class="section-intro">
          <p class="tech-label">// STRATEGIC PRINCIPLES</p>
          <h2 class="section-heading">BUILD DIFFERENT. CREATE IMPACT.</h2>
        </div>

        <div class="principles-grid">
          <?php foreach ($principles as $principle) : ?>
            <article class="principle-card">
              <span class="principle-indicator"></span>
              <h3 class="principle-title"><?php echo $principle['title']; ?></h3>
              <p class="principle-text"><?php echo $principle['text']; ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- =========================================================
         FEATURED COLLECTION
    ========================================================== -->
    <section class="collection" id="gallery">
      <div class="container">

        <p class="section-index">READY_RESERVES // 03</p>

        <div class="section-intro section-intro-split">
          <div>
            <p class="tech-label">// SPEC_OPS_GEAR</p>
            <h2 class="section-heading">FEATURED COLLECTION</h2>
          </div>
          <p class="collection-meta">DEPT_LIST_V25 // <?php echo count($products); ?> PIECES LOADED</p>
        </div>

        <div class="product-grid">
          <?php foreach ($products as $product) : ?>
            <article class="product-card">
              <div class="product-image">
                <span class="product-tag">SYS.ACTIVE</span>
                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['alt']; ?>" data-fallback="product">
              </div>
              <div class="product-info">
                <p class="product-code"><?php echo $product['code']; ?></p>
                <h3 class="product-name"><?php echo $product['name']; ?></h3>
                <p class="product-price"><?php echo $product['price']; ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- =========================================================
         FEATURE CTA
    ========================================================== -->
    <section class="feature-cta">
      <div class="feature-cta-bg">
        <img src="images/homepagebg.png" alt="" data-fallback="hero">
        <div class="feature-cta-overlay"></div>
      </div>
      <div class="container feature-cta-content">
        <p class="section-index section-index-light">VANGUARD_BROADCAST // 04</p>
        <p class="tech-label">// MANDATE_05</p>
        <h2 class="feature-cta-heading">DESIGNED FOR THOSE WHO MOVE DIFFERENT.</h2>
        <a href="#gallery" class="btn btn-outline">VIEW COLLECTION <span class="arrow">→</span></a>
      </div>
    </section>

    <!-- =========================================================
         BRAND VALUES / VALIDATED INTEGRATIONS
    ========================================================== -->
    <section class="values">
      <div class="container">

        <p class="section-index">SYNDICATE_ALLIES // 05</p>

        <div class="section-intro">
          <p class="tech-label">// VALIDATED_INTEGRATIONS</p>
          <h2 class="section-heading">TRUSTED. TESTED. PROVEN.</h2>
        </div>

        <div class="values-grid">
          <?php foreach ($partners as $partner) : ?>
            <div class="value-block">
              <h3 class="value-title"><?php echo $partner['name']; ?></h3>
              <p class="value-text"><?php echo $partner['tag']; ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <!-- =========================================================
         FINAL CTA + CONTACT FORM
    ========================================================== -->
    <section class="final-cta" id="contact">
      <div class="container final-cta-content">
        <p class="section-index">TERMINAL_COMMAND // 06</p>
        <p class="tech-label">// INITIALIZE_DEPLOYMENT</p>
        <h2 class="final-cta-heading">BUILD DIFFERENT. CREATE IMPACT.</h2>
        <a href="#gallery" class="btn btn-primary">EXPLORE COLLECTION <span class="arrow">→</span></a>

      </div>
    </section>

<?php require 'includes/footer.php'; ?>
