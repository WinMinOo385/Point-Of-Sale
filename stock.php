<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="stock-app">
    <div class="stock-container">
        <section class="stock-list product-section" aria-labelledby="products-heading">
            <header class="stock-toolbar">
                <h1 id="products-heading">Product Stock</h1>
                <div class="stock-actions">
                    <input id="searchInputStock" type="search" placeholder="Search products..." aria-label="Search products" />
                    <select id="sortSelect" aria-label="Sort products">
                        <option value="name">Sort: Name</option>
                        <option value="qty">Sort: Quantity</option>
                        <option value="price">Sort: Price</option>
                    </select>
                    <button id="addProductBtn" class="add-to-cart-btn" aria-haspopup="dialog">Add Product</button>
                </div>
            </header>

            <div class="table-wrapper" tabindex="0">
                <table class="products-table" aria-describedby="products-heading">
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">SKU</th>
                            <th scope="col" class="num">Qty</th>
                            <th scope="col" class="num">Price</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTbody">
                        <tr><td colspan="5" style="text-align:center; padding: 24px;">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="stock-form-panel product-section" aria-label="Product details">
            <form id="productForm" novalidate>
                <input type="hidden" id="productId" />
                <div class="form-group">
                    <label for="name">Name</label>
                    <input id="name" name="name" type="text" required maxlength="100" />
                    <div class="field-error" id="nameError" role="alert"></div>
                </div>
                <div class="form-group">
                    <label for="sku">SKU</label>
                    <input id="sku" name="sku" type="text" maxlength="64" />
                    <div class="field-error" id="skuError" role="alert"></div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="stock">Quantity</label>
                        <input id="stock" name="stock" type="number" min="0" step="1" required />
                        <div class="qty-quick">
                            <button type="button" data-delta="-10" class="qty-btn" aria-label="Decrease by 10">-10</button>
                            <button type="button" data-delta="-1" class="qty-btn" aria-label="Decrease by 1">-1</button>
                            <button type="button" data-delta="1" class="qty-btn" aria-label="Increase by 1">+1</button>
                            <button type="button" data-delta="10" class="qty-btn" aria-label="Increase by 10">+10</button>
                        </div>
                        <div class="field-error" id="stockError" role="alert"></div>
                    </div>
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input id="price" name="price" type="number" min="0" step="0.01" required />
                        <div class="field-error" id="priceError" role="alert"></div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="notes">Notes (optional)</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="add-to-cart-btn" id="saveBtn">Save</button>
                    <button type="button" class="secondary-btn" id="resetBtn">Reset</button>
                </div>
            </form>
        </aside>
    </div>

    <!-- Modal for add/edit on mobile or when requested -->
    <div id="productModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" hidden>
        <div class="modal-backdrop" data-dismiss></div>
        <div class="modal-dialog" role="document">
            <header class="modal-header">
                <h2 id="modalTitle">Add Product</h2>
                <button class="icon-btn" data-dismiss aria-label="Close">×</button>
            </header>
            <div class="modal-body">
                <!-- Form will be cloned here on small screens -->
            </div>
            <footer class="modal-footer">
                <button class="secondary-btn" data-dismiss>Cancel</button>
                <button class="primary-btn" id="modalSaveBtn">Save</button>
            </footer>
        </div>
    </div>
</main>

<script src="public/js/stock.js" defer></script>
<link rel="stylesheet" href="public/css/stock.css" />

<?php require_once __DIR__ . '/includes/footer.php'; ?>


