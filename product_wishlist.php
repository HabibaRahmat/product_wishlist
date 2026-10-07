<?php
/*
Plugin Name: Product Wishlist
Description: A simple product wishlist plugin.
Version: 1.0
Author: Habiba
*/

if (!defined('ABSPATH')) {
    exit;
}


/* ==============================
   ADD WISHLIST BUTTON
================================= */

function pw_add_wishlist_button() {

    global $post;

    if (!$post) {
        return;
    }

    $product_id = $post->ID;

    echo '<button 
        class="pw-wishlist-button" 
        data-product-id="' . esc_attr($product_id) . '">
        ❤️ Add to Wishlist
    </button>';
}

add_action(
    'woocommerce_after_add_to_cart_button',
    'pw_add_wishlist_button'
);


/* ==============================
   WISHLIST PAGE
================================= */

function pw_wishlist_page() {

    ob_start();

    ?>

    <div class="pw-wishlist">

        <h2>❤️ My Wishlist</h2>

        <div id="pw-wishlist-products">
            <p>Loading wishlist...</p>
        </div>

    </div>

    <?php

    return ob_get_clean();
}

add_shortcode('product_wishlist', 'pw_wishlist_page');


/* ==============================
   AJAX GET PRODUCTS
================================= */

function pw_get_wishlist_products() {

    if (!isset($_POST['product_ids'])) {
        wp_send_json_success('<p>Your wishlist is empty.</p>');
    }

    $product_ids = json_decode(
        stripslashes($_POST['product_ids']),
        true
    );

    if (empty($product_ids)) {
        wp_send_json_success('<p>Your wishlist is empty.</p>');
    }

    ob_start();

    echo '<div class="pw-products">';

    foreach ($product_ids as $product_id) {

        $product = wc_get_product($product_id);

        if (!$product) {
            continue;
        }

        ?>

        <div class="pw-product">

            <?php echo $product->get_image('medium'); ?>

            <h3>
                <?php echo esc_html($product->get_name()); ?>
            </h3>

            <p class="pw-price">
                <?php echo wp_kses_post($product->get_price_html()); ?>
            </p>

            <a
                class="pw-view-product"
                href="<?php echo esc_url(get_permalink($product_id)); ?>">
                View Product
            </a>

            <button
                class="pw-remove"
                data-product-id="<?php echo esc_attr($product_id); ?>">
                ❌ Remove
            </button>

        </div>

        <?php
    }

    echo '</div>';

    wp_send_json_success(ob_get_clean());
}

add_action(
    'wp_ajax_pw_get_wishlist',
    'pw_get_wishlist_products'
);

add_action(
    'wp_ajax_nopriv_pw_get_wishlist',
    'pw_get_wishlist_products'
);


/* ==============================
   JAVASCRIPT
================================= */

function pw_wishlist_scripts() {
    ?>

    <script>

    document.addEventListener("DOMContentLoaded", function() {

        /* ADD TO WISHLIST */

        document.querySelectorAll(".pw-wishlist-button").forEach(function(button) {

            button.addEventListener("click", function() {

                let productId = this.getAttribute("data-product-id");

                let wishlist = JSON.parse(
                    localStorage.getItem("pw_wishlist") || "[]"
                );

                if (!wishlist.includes(productId)) {

                    wishlist.push(productId);

                    localStorage.setItem(
                        "pw_wishlist",
                        JSON.stringify(wishlist)
                    );

                    this.innerHTML = "❤️ Added to Wishlist";

                } else {

                    alert("Product is already in your wishlist.");

                }

            });

        });


        /* LOAD WISHLIST */

        let wishlistBox = document.getElementById(
            "pw-wishlist-products"
        );

        if (wishlistBox) {

            let wishlist = JSON.parse(
                localStorage.getItem("pw_wishlist") || "[]"
            );

            if (wishlist.length === 0) {

                wishlistBox.innerHTML =
                    "<p>Your wishlist is empty.</p>";

                return;
            }

            let formData = new FormData();

            formData.append(
                "action",
                "pw_get_wishlist"
            );

            formData.append(
                "product_ids",
                JSON.stringify(wishlist)
            );

            fetch(
                "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
                {
                    method: "POST",
                    body: formData
                }
            )
            .then(response => response.json())
            .then(data => {

                if (data.success) {

                    wishlistBox.innerHTML = data.data;

                    addRemoveButtons();

                }

            });

        }


        /* REMOVE PRODUCT */

        function addRemoveButtons() {

            document.querySelectorAll(".pw-remove").forEach(function(button) {

                button.addEventListener("click", function() {

                    let productId =
                        this.getAttribute("data-product-id");

                    let wishlist = JSON.parse(
                        localStorage.getItem("pw_wishlist") || "[]"
                    );

                    wishlist = wishlist.filter(
                        id => id !== productId
                    );

                    localStorage.setItem(
                        "pw_wishlist",
                        JSON.stringify(wishlist)
                    );

                    this.parentElement.remove();

                    if (wishlist.length === 0) {

                        wishlistBox.innerHTML =
                            "<p>Your wishlist is empty.</p>";

                    }

                });

            });

        }

    });

    </script>

    <?php
}

add_action('wp_footer', 'pw_wishlist_scripts');