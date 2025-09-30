// Notification system
function showNotification(message, type = 'info') {
    // Remove any existing notifications
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    // Add styles if not already present
    if (!document.querySelector('#notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 10000;
                max-width: 400px;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                animation: slideIn 0.3s ease-out;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .notification-success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            
            .notification-error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            
            .notification-info {
                background: #d1ecf1;
                border: 1px solid #bee5eb;
                color: #0c5460;
            }
            
            .notification-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .notification-close {
                background: none;
                border: none;
                color: inherit;
                cursor: pointer;
                padding: 0;
                margin-left: auto;
                opacity: 0.7;
                transition: opacity 0.2s;
            }
            
            .notification-close:hover {
                opacity: 1;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(styles);
    }

    // Add to page
    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideIn 0.3s ease-out reverse';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Cart functionality
let cart = [];
let cartTotal = 0;
let selectedCustomerId = null;

// Search functionality
document.getElementById('searchInput').addEventListener('input', function (e) {
    const searchTerm = e.target.value.toLowerCase();
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        const productName = card.dataset.name.toLowerCase();
        if (productName.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Quantity controls
function increaseQuantity(pid) {
    const input = document.getElementById(`qty-${pid}`);
    const max = parseInt(input.getAttribute('max'));
    const current = parseInt(input.value);

    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQuantity(pid) {
    const input = document.getElementById(`qty-${pid}`);
    const current = parseInt(input.value);

    if (current > 1) {
        input.value = current - 1;
    }
}

// Add to cart
function addToCart(pid, name, price, stock) {
    // Check if product is out of stock
    if (stock <= 0) {
        showNotification('This product is out of stock!', 'error');
        return;
    }

    const quantityInput = document.getElementById(`qty-${pid}`);
    const quantity = parseInt(quantityInput.value);

    if (quantity > stock) {
        showNotification('Not enough stock available!', 'error');
        return;
    }

    // Check if item already exists in cart
    const existingItem = cart.find(item => item.pid === pid);

    if (existingItem) {
        // Update quantity if adding more than available
        if (existingItem.quantity + quantity > stock) {
            showNotification('Not enough stock available!', 'error');
            return;
        }
        existingItem.quantity += quantity;
    } else {
        // Add new item to cart
        cart.push({
            pid: pid,
            name: name,
            price: price,
            quantity: quantity,
            stock: stock
        });
    }

    updateCartDisplay();
    showAddToCartAnimation(pid);
    showNotification(`${name} added to cart!`, 'success');
}

// Show animation when item is added to cart
function showAddToCartAnimation(pid) {
    const button = document.querySelector(`[onclick*="addToCart(${pid}"]`);
    const originalText = button.innerHTML;

    button.innerHTML = '<i class="fas fa-check"></i> Added!';
    button.style.backgroundColor = '#28a745';
    setTimeout(() => {
        button.innerHTML = originalText;
        button.style.backgroundColor = '#85a0c7';
    }, 1000);
}

// Update cart display
function updateCartDisplay() {
    const cartItemsContainer = document.getElementById('cartItems');
    if (cart.length === 0) {
        cartItemsContainer.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
            </div>
        `;
    } else {
        cartItemsContainer.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                </div>
                <div class="cart-item-controls">
                    <button class="cart-qty-btn" onclick="updateCartQuantity(${item.pid}, -1)">-</button>
                    <span class="cart-qty">${item.quantity}</span>
                    <button class="cart-qty-btn" onclick="updateCartQuantity(${item.pid}, 1)">+</button>
                    <button class="remove-item-btn" onclick="removeFromCart(${item.pid})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    updateCartTotal();
}

// Update cart quantity
function updateCartQuantity(pid, change) {
    const item = cart.find(item => item.pid === pid);

    if (item) {
        const newQuantity = item.quantity + change;
        if (newQuantity <= 0) {
            removeFromCart(pid);
        } else if (newQuantity <= item.stock) {
            item.quantity = newQuantity;
            updateCartDisplay();
        } else {
            showNotification('Not enough stock available!', 'error');
        }
    }
}

// Remove from cart
function removeFromCart(pid) {
    cart = cart.filter(item => item.pid !== pid);
    updateCartDisplay();
}

// Clear cart
function clearCart() {
    if (cart.length === 0) return;

    // Clear cart directly without confirmation
    cart = [];
    updateCartDisplay();
    showNotification('Cart cleared successfully!', 'success');
}

// Update cart total
function updateCartTotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * 0.1; // 10% tax
    const total = subtotal + tax;

    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('total').textContent = `$${total.toFixed(2)}`;

    // Update checkout button - require both cart items and customer selection
    const checkoutBtn = document.querySelector('.checkout-btn');
    if (cart.length === 0) {
        checkoutBtn.disabled = true;
        checkoutBtn.title = "Cart is empty";
    } else if (!selectedCustomerId) {
        checkoutBtn.disabled = true;
        checkoutBtn.title = "Please select a customer to proceed with checkout";
    } else {
        checkoutBtn.disabled = false;
        checkoutBtn.title = "";
    }
}

// Checkout
function checkout() {
    if (cart.length === 0) {
        return;
    }

    if (!selectedCustomerId) {
        return;
    }


    // Here you would typically send the cart data to your server
    // For now, we'll just show a confirmation
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = total * 0.1;
    const finalTotal = total + tax;

    // Get selected customer name for confirmation
    const customerSelect = document.getElementById('customerSelect');
    const selectedCustomerName = customerSelect.options[customerSelect.selectedIndex].text;

    const cartSummary = cart.map(item =>
        `${item.name} x${item.quantity} = $${(item.price * item.quantity).toFixed(2)}`
    ).join('\n');
    const confirmation = `
Checkout Summary:
${cartSummary}

Subtotal: $${total.toFixed(2)}
Tax (10%): $${tax.toFixed(2)}
Total: $${finalTotal.toFixed(2)}

Proceed with checkout?
    `;

    // Proceed directly with checkout - no confirmation needed
    // Disable checkout button to prevent double submission
        const checkoutBtn = document.querySelector('.checkout-btn');
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        // Send cart data to server for REAL database processing
        fetch('utility/checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cart: cart,
                customer_id: selectedCustomerId // Use selected customer
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update product stock displays on the page with REAL updated values
                    if (data.updated_products) {
                        data.updated_products.forEach(product => {
                            updateProductStock(product.pid, product.stock);
                        });
                    }

                    showNotification(`Checkout Complete! Sale ID: ${data.sale_id} | Total: $${data.total.toFixed(2)}`, 'success');
                    // Open invoice in new window
                    const invoiceWindow = window.open(`utility/generate_invoice.php?sale_id=${data.sale_id}`, '_blank', 'width=900,height=700,scrollbars=yes,resizable=yes');
                    // Invoice window opened silently
                    
                    cart = [];
                    updateCartDisplay();
                } else {
                    showNotification(`Checkout failed: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred during checkout. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable checkout button
                checkoutBtn.disabled = false;
                checkoutBtn.innerHTML = '<i class="fas fa-credit-card"></i> Checkout';
            });
    }

// Update product stock and button state
function updateProductStock(pid, newStock) {
    // Update stock display
    const stockElement = document.querySelector(`[onclick*="addToCart(${pid}"]`)
        ?.closest('.product-card')
        ?.querySelector('.product-stock');
    if (stockElement) {
        stockElement.textContent = `Stock: ${newStock}`;
    }

    // Update quantity input max value
    const qtyInput = document.getElementById(`qty-${pid}`);
    if (qtyInput) {
        qtyInput.setAttribute('max', newStock);
        if (parseInt(qtyInput.value) > newStock) {
            qtyInput.value = Math.max(1, newStock);
        }
    }

    // Update add to cart button based on stock
    const addToCartBtn = document.querySelector(`[onclick*="addToCart(${pid}"]`);
    if (addToCartBtn) {
        if (newStock <= 0) {
            // Out of stock - disable button and change text
            addToCartBtn.disabled = true;
            addToCartBtn.className = 'add-to-cart-btn out-of-stock';
            addToCartBtn.innerHTML = '<i class="fas fa-times-circle"></i> Out of Stock';
            addToCartBtn.onclick = null;

            // Also disable quantity controls
            const qtyControls = addToCartBtn.closest('.product-card').querySelector('.quantity-controls');
            if (qtyControls) {
                const buttons = qtyControls.querySelectorAll('.qty-btn');
                buttons.forEach(btn => btn.disabled = true);
                if (qtyInput) qtyInput.disabled = true;
            }
        } else {
            // In stock - enable button
            addToCartBtn.disabled = false;
            addToCartBtn.className = 'add-to-cart-btn';
            addToCartBtn.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';

            // Re-enable quantity controls
            const qtyControls = addToCartBtn.closest('.product-card').querySelector('.quantity-controls');
            if (qtyControls) {
                const buttons = qtyControls.querySelectorAll('.qty-btn');
                buttons.forEach(btn => btn.disabled = false);
                if (qtyInput) qtyInput.disabled = false;
            }
        }
    }
}

// Initialize cart display
document.addEventListener('DOMContentLoaded', function () {
    updateCartDisplay();
});

// Customer selection functionality
function updateSelectedCustomer(customerId) {
    selectedCustomerId = customerId ? parseInt(customerId) : null;

    // Update checkout button state
    const checkoutBtn = document.querySelector('.checkout-btn');
    if (cart.length > 0 && !selectedCustomerId) {
        checkoutBtn.disabled = true;
        checkoutBtn.title = "Please select a customer to proceed with checkout";
    } else {
        checkoutBtn.disabled = cart.length === 0;
        checkoutBtn.title = "";
    }

    // Show customer selection status
    const customerSelect = document.getElementById('customerSelect');
    if (customerId) {
        const selectedOption = customerSelect.options[customerSelect.selectedIndex];
        console.log(`Customer selected: ${selectedOption.text} (ID: ${customerId})`);
    } else {
        console.log('No customer selected');
    }
}

// Prevent form submission on quantity input enter
document.addEventListener('keypress', function (e) {
    if (e.target.type === 'number' && e.key === 'Enter') {
        e.preventDefault();
    }
});
