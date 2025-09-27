// Cart functionality
let cart = [];
let cartTotal = 0;

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
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
        alert('This product is out of stock!');
        return;
    }
    
    const quantityInput = document.getElementById(`qty-${pid}`);
    const quantity = parseInt(quantityInput.value);
    
    if (quantity > stock) {
        alert('Not enough stock available!');
        return;
    }
    
    // Check if item already exists in cart
    const existingItem = cart.find(item => item.pid === pid);
    
    if (existingItem) {
        // Update quantity if adding more than available
        if (existingItem.quantity + quantity > stock) {
            alert('Not enough stock available!');
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
            alert('Not enough stock available!');
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
    
    if (confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCartDisplay();
    }
}

// Update cart total
function updateCartTotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * 0.1; // 10% tax
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
    document.getElementById('total').textContent = `$${total.toFixed(2)}`;
    
    // Update checkout button
    const checkoutBtn = document.querySelector('.checkout-btn');
    checkoutBtn.disabled = cart.length === 0;
}

// Checkout
function checkout() {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }
    
    // Here you would typically send the cart data to your server
    // For now, we'll just show a confirmation
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = total * 0.1;
    const finalTotal = total + tax;
    
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
    
    if (confirm(confirmation)) {
        // Disable checkout button to prevent double submission
        const checkoutBtn = document.querySelector('.checkout-btn');
        checkoutBtn.disabled = true;
        checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        // Send cart data to server for REAL database processing
        fetch('checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cart: cart,
                customer_id: 1 // Default customer
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
                
                alert(`REAL Checkout Complete!\nSale ID: ${data.sale_id}\nTotal: $${data.total.toFixed(2)}\nProduct quantities decreased in database!`);
                cart = [];
                updateCartDisplay();
            } else {
                alert(`Checkout failed: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during checkout. Please try again.');
        })
        .finally(() => {
            // Re-enable checkout button
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = '<i class="fas fa-credit-card"></i> Checkout';
        });
    }
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
document.addEventListener('DOMContentLoaded', function() {
    updateCartDisplay();
});

// Prevent form submission on quantity input enter
document.addEventListener('keypress', function(e) {
    if (e.target.type === 'number' && e.key === 'Enter') {
        e.preventDefault();
    }
});
