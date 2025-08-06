<div class="container py-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h4 class="mb-0 fw-bold">Keranjang Belanja</h4>
                </div>
                <div class="card-body">
                    <div id="cartItems">
                        <!-- Cart items akan ditampilkan di sini -->
                    </div>
                    <div id="emptyCart" class="text-center py-5 d-none">
                        <i class="bi bi-cart-x display-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">Keranjang Anda Kosong</h5>
                        <p class="text-muted">Silakan tambahkan produk ke keranjang terlebih dahulu</p>
                        <a href="<?= base_url('katalog') ?>" class="btn btn-primary">Mulai Belanja</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0 fw-bold">Ringkasan Pesanan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Item:</span>
                        <span id="totalItems">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Total Harga:</span>
                        <span class="fw-bold text-primary" id="totalPrice">Rp 0</span>
                    </div>
                    <button id="checkoutBtn" class="btn btn-success w-100" disabled>
                        <i class="bi bi-bag-check me-2"></i>Lanjut ke Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<script>
class CartManager {
    constructor() {
        this.cartKey = 'shopping_cart';
        this.init();
    }

    init() {
        this.loadCart();
        this.bindEvents();
    }

    getCart() {
        const cart = localStorage.getItem(this.cartKey);
        return cart ? JSON.parse(cart) : [];
    }

    saveCart(cart) {
        localStorage.setItem(this.cartKey, JSON.stringify(cart));
    }

    async loadCart() {
        const cart = this.getCart();
        console.log('Cart data from localStorage:', cart); // Debug log
        
        if (cart.length === 0) {
            this.showEmptyCart();
            return;
        }

        try {
            // Hanya menggunakan GET request
            const cartData = encodeURIComponent(JSON.stringify(cart));
            const url = `<?= base_url('cart/getProducts') ?>?cart=${cartData}`;
            console.log('Request URL:', url); // Debug log
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();
            console.log('API Response:', result); // Debug log
            
            if (!response.ok) {
                console.error('HTTP Error:', response.status, response.statusText);
                this.showEmptyCart();
                return;
            }
            
            if (result.success) {
                this.renderCart(result.data);
                this.updateSummary(result.data);
            } else {
                console.error('API Error:', result.message);
                this.showEmptyCart();
            }
        } catch (error) {
            console.error('Error loading cart:', error);
            this.showEmptyCart();
        }
    }

    renderCart(items) {
        const cartContainer = document.getElementById('cartItems');
        const emptyCart = document.getElementById('emptyCart');
        
        if (items.length === 0) {
            this.showEmptyCart();
            return;
        }

        emptyCart.classList.add('d-none');
        cartContainer.innerHTML = '';

        items.forEach(item => {
            // Gunakan variant_id jika ada, fallback ke id untuk backward compatibility
            const itemId = item.variant_id || item.id;
            const cartItemHtml = `
                <div class="border-bottom py-3" data-id="${itemId}" data-variant-id="${item.variant_id || ''}" data-product-id="${item.id}">
                    <!-- Desktop Layout -->
                    <div class="d-none d-md-block">
                        <div class="row align-items-center g-3">
                            <div class="col-md-2">
                                <div class="text-center">
                                    <img src="<?= base_url('uploads/') ?>${item.photo || 'no-image.png'}" class="img-fluid rounded" width="80" height="80" style="object-fit: cover;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1">${item.name}</h6>
                                ${item.size && item.color ? `<small class="text-muted">${item.size} - ${item.color}</small><br>` : ''}
                                <div class="text-primary fw-semibold">Rp ${this.formatNumber(item.harga)}</div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center justify-content-center">
                                    <button class="btn btn-sm btn-outline-secondary quantity-btn" data-action="decrease">-</button>
                                    <input type="number" class="form-control mx-2 text-center quantity-input" value="${item.quantity}" min="1" data-id="${itemId}" style="width: 70px;">
                                    <button class="btn btn-sm btn-outline-secondary quantity-btn" data-action="increase">+</button>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <div class="fw-bold">Rp ${this.formatNumber(item.harga * item.quantity)}</div>
                            </div>
                            <div class="col-md-1 text-end">
                                <button class="btn btn-sm btn-outline-danger remove-item" data-id="${itemId}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Layout -->
                    <div class="d-block d-md-none">
                        <div class="row g-3">
                            <div class="col-4">
                                <img src="<?= base_url('uploads/') ?>${item.photo || 'no-image.png'}" class="img-fluid rounded w-100" style="height: 80px; object-fit: cover;">
                            </div>
                            <div class="col-8">
                                <h6 class="mb-1">${item.name}</h6>
                                ${item.size && item.color ? `<small class="text-muted">${item.size} - ${item.color}</small><br>` : ''}
                                <div class="text-primary fw-semibold mb-2">Rp ${this.formatNumber(item.harga)}</div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <button class="btn btn-sm btn-outline-secondary quantity-btn" data-action="decrease">-</button>
                                        <input type="number" class="form-control mx-2 text-center quantity-input" value="${item.quantity}" min="1" data-id="${itemId}" style="width: 50px;">
                                        <button class="btn btn-sm btn-outline-secondary quantity-btn" data-action="increase">+</button>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger remove-item" data-id="${itemId}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <small class="text-muted">Subtotal:</small>
                            </div>
                            <div class="col-6 text-end">
                                <span class="fw-bold">Rp ${this.formatNumber(item.harga * item.quantity)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            cartContainer.innerHTML += cartItemHtml;
        });
    }

    updateSummary(items) {
        const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
        const totalPrice = items.reduce((sum, item) => sum + (item.harga * item.quantity), 0);

        document.getElementById('totalItems').textContent = totalItems;
        document.getElementById('totalPrice').textContent = formatRupiahIntl(totalPrice);
        
        const checkoutBtn = document.getElementById('checkoutBtn');
        checkoutBtn.disabled = items.length === 0;
    }

    showEmptyCart() {
        document.getElementById('cartItems').innerHTML = '';
        document.getElementById('emptyCart').classList.remove('d-none');
        this.updateSummary([]);
    }

    updateQuantity(itemId, newQuantity) {
        const cart = this.getCart();
        
        // Cari item berdasarkan variant_id atau id
        const itemIndex = cart.findIndex(item => 
            (item.variant_id && item.variant_id == itemId) || 
            (item.id && item.id == itemId)
        );
        
        if (itemIndex !== -1) {
            if (newQuantity <= 0) {
                cart.splice(itemIndex, 1);
            } else {
                cart[itemIndex].quantity = newQuantity;
            }
            this.saveCart(cart);
            this.loadCart();
            this.updateCartBadge();
        }
    }

    removeItem(itemId) {
        const cart = this.getCart();
        
        // Filter item berdasarkan variant_id atau id
        const updatedCart = cart.filter(item => 
            !(item.variant_id && item.variant_id == itemId) && 
            !(item.id && item.id == itemId)
        );
        
        this.saveCart(updatedCart);
        this.loadCart();
        this.updateCartBadge();
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('quantity-btn') || e.target.closest('.quantity-btn')) {
                const btn = e.target.closest('.quantity-btn');
                const action = btn.dataset.action;
                const input = btn.parentElement.querySelector('.quantity-input');
                const currentValue = parseInt(input.value);
                
                if (action === 'increase') {
                    input.value = currentValue + 1;
                } else if (action === 'decrease' && currentValue > 1) {
                    input.value = currentValue - 1;
                }
                
                this.updateQuantity(input.dataset.id, parseInt(input.value));
            }
            
            if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
                const btn = e.target.closest('.remove-item');
                this.removeItem(btn.dataset.id);
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('quantity-input')) {
                const newQuantity = parseInt(e.target.value);
                if (newQuantity > 0) {
                    this.updateQuantity(e.target.dataset.id, newQuantity);
                }
            }
        });

        document.getElementById('checkoutBtn').addEventListener('click', () => {
            window.location.href = '<?= base_url('checkout') ?>';
        });
    }

    formatNumber(num) {
        return formatRupiahIntl(num).replace('IDR', '').trim();
    }

    updateCartBadge() {
        const cart = this.getCart();
        const totalItems = cart.reduce((sum, item) => {
            // Support both variant_id and id structure
            return sum + (item.quantity || 0);
        }, 0);
        
        // Update cart badge if exists
        const cartBadge = document.querySelector('.cart-badge');
        if (cartBadge) {
            cartBadge.textContent = totalItems;
            cartBadge.style.display = totalItems > 0 ? 'inline' : 'none';
        }
    }
}

// Initialize cart manager
document.addEventListener('DOMContentLoaded', () => {
    new CartManager();
});
</script>
