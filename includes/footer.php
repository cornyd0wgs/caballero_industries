<?php

require_once __DIR__ . '/../helpers/stuff.php';

?>
</main>

<?php if (($current_page ?? '') === 'account') : ?>

<footer class="account-footer">
    <div class="container account-footer-inner">
        <p>&copy; <?php echo $site_year; ?> CABALLERO INDUSTRIES</p>
        <a href="<?php echo BASE_URL; ?>">RETURN TO STORE →</a>
    </div>
</footer>

<?php elseif (($current_page ?? '') !== 'admin') : ?>

<!-- =========================================================
     FOOTER
========================================================== -->

<footer class="site-footer">

    <div class="container footer-inner">

        <div class="footer-brand">

            <img
                src="<?php echo BASE_URL; ?>assets/logo.png"
                alt="<?php echo $site_name; ?> logo"
                class="footer-logo"
            >

            <p class="footer-text">
                Next-generation design systems applied to high-utility tactical outerwear.
                Forged with resilient materials for modern operators.
            </p>

            <div class="footer-social">

                <a
                    href="#"
                    class="social-icon"
                    aria-label="Instagram"
                >
                    IG
                </a>

                <a
                    href="#"
                    class="social-icon"
                    aria-label="Facebook"
                >
                    FB
                </a>

                <a
                    href="#"
                    class="social-icon"
                    aria-label="X (Twitter)"
                >
                    X
                </a>

            </div>

        </div>


        <?php foreach ($footer_columns as $columnTitle => $links) : ?>

            <div class="footer-col">

                <h4 class="footer-col-title">
                    <?php echo $columnTitle; ?>
                </h4>

                <ul class="footer-links">

                    <?php foreach ($links as $link) : ?>

                        <li>
                            <a href="<?php echo $link[1]; ?>">
                                <?php echo $link[0]; ?>
                            </a>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endforeach; ?>

    </div>


    <div class="footer-bottom">

        <div class="container footer-bottom-inner">

            <p>
                &copy; <?php echo $site_year; ?>
                CABALLERO INDUSTRIES. ALL RIGHTS RESERVED.
            </p>

            <p class="footer-protocol">
                SECURE ACCESS // PROTOCOL_V1
            </p>

        </div>

    </div>

</footer>

<?php endif; ?>

<script src="<?php echo BASE_URL; ?>script.js?v=<?php echo filemtime(__DIR__ . '/../script.js'); ?>"></script>

</body>
</html>
