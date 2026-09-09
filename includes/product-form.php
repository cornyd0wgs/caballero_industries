<?php
$form_action = $form_action ?? '';
$submit_label = $submit_label ?? 'SAVE PRODUCT';
$is_editing = $is_editing ?? false;
$is_featured = $is_featured ?? 0;
$units_sold = $units_sold ?? 0;
$is_popular_today = $is_popular_today ?? false;
?>

<?php if (!empty($errors)) : ?>
    <ul class="form-message form-message-error">
        <?php foreach ($errors as $error) : ?>
            <li><?php echo safe_output($error); ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<form
    class="admin-form"
    method="post"
    action="<?php echo safe_output($form_action); ?>"
    enctype="multipart/form-data"
>
    <?php echo csrf_field(); ?>

    <?php if ($is_editing && !empty($product['image'])) : ?>
        <div class="form-row">
            <label>CURRENT IMAGE</label>
            <img
                src="<?php echo safe_output(asset_url($product['image'])); ?>"
                alt="<?php echo safe_output($old['name']); ?>"
                class="admin-current-image"
            >
        </div>
    <?php endif; ?>

    <div class="form-row">
        <label for="product_code">PRODUCT CODE</label>
        <input
            type="text"
            id="product_code"
            name="product_code"
            required
            value="<?php echo safe_output($old['product_code']); ?>"
        >
    </div>

    <div class="form-row">
        <label for="name">NAME</label>
        <input
            type="text"
            id="name"
            name="name"
            required
            value="<?php echo safe_output($old['name']); ?>"
        >
    </div>

    <div class="form-row">
        <label for="description">DESCRIPTION</label>
        <textarea
            id="description"
            name="description"
            rows="4"
        ><?php echo safe_output($old['description']); ?></textarea>
    </div>

    <div class="form-row-split">
        <div class="form-row">
            <label for="price">PRICE (&#8369;)</label>
            <input
                type="number"
                id="price"
                name="price"
                step="0.01"
                min="0.01"
                required
                value="<?php echo safe_output($old['price']); ?>"
            >
        </div>

        <div class="form-row">
            <label for="discount_price">DISCOUNT PRICE (&#8369;, optional)</label>
            <input
                type="number"
                id="discount_price"
                name="discount_price"
                step="0.01"
                min="0.01"
                value="<?php echo safe_output($old['discount_price']); ?>"
            >
            <small class="form-help">Leave blank when the product is not on sale.</small>
        </div>
    </div>

    <?php if ($is_editing) : ?>
        <div class="form-row">
            <label>CURRENT STOCK</label>
            <div class="admin-stock-readonly">
                <strong><?php echo (int) $product['quantity']; ?> UNITS</strong>
                <a href="restock-product.php?id=<?php echo (int) $product['id']; ?>">RESTOCK PRODUCT →</a>
            </div>
            <small class="form-help">Stock is managed separately so editing product details cannot overwrite inventory by accident.</small>
        </div>
    <?php else : ?>
        <div class="form-row">
            <label for="quantity">INITIAL STOCK</label>
            <input
                type="number"
                id="quantity"
                name="quantity"
                min="0"
                step="1"
                required
                value="<?php echo safe_output($old['quantity']); ?>"
            >
        </div>
    <?php endif; ?>

    <div class="form-row">
        <label for="image">
            <?php echo $is_editing ? 'REPLACE IMAGE (optional)' : 'PRODUCT IMAGE (optional)'; ?>
            — JPG/PNG/WEBP, up to 5MB
        </label>
        <input
            type="file"
            id="image"
            name="image"
            accept=".jpg,.jpeg,.png,.webp"
        >
    </div>

    <div class="form-row-checkboxes">
        <label class="checkbox-label">
            <input
                type="checkbox"
                name="is_featured"
                <?php echo $is_featured ? 'checked' : ''; ?>
            >
            Show in Featured Collection
        </label>
    </div>

    <?php if ($is_editing) : ?>
        <div class="auto-popularity">
            <span>AUTOMATIC POPULARITY</span>
            <strong><?php echo (int) $units_sold; ?> SOLD</strong>
            <em><?php echo $is_popular_today ? 'POPULAR TODAY' : 'STANDARD'; ?></em>
        </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">
        <?php echo safe_output($submit_label); ?> <span class="arrow">→</span>
    </button>
</form>
