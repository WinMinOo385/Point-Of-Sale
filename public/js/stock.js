(() => {
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    function toast(message, type = 'info') {
        if (window.showNotification) return window.showNotification(message, type);
        const n = document.createElement('div');
        n.className = `notification notification-${type}`;
        n.textContent = message;
        n.style.position = 'fixed';
        n.style.top = '16px';
        n.style.right = '16px';
        n.style.background = type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1';
        n.style.padding = '12px 16px';
        n.style.borderRadius = '6px';
        n.style.zIndex = 10000;
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 3000);
    }

    const api = {
        async list(q = '', sort = 'name') {
            const res = await fetch(`utility/products_api.php?action=list&q=${encodeURIComponent(q)}&sort=${encodeURIComponent(sort)}`);
            return res.json();
        },
        async create(data) {
            const res = await fetch('utility/products_api.php?action=create', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            return res.json();
        },
        async update(id, data) {
            const res = await fetch(`utility/products_api.php?action=update&id=${id}`, { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            return res.json();
        },
        async adjust(id, delta) {
            const res = await fetch(`utility/products_api.php?action=adjust&id=${id}`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ delta }) });
            return res.json();
        },
        async remove(id) {
            const res = await fetch(`utility/products_api.php?action=delete&id=${id}`, { method: 'DELETE' });
            return res.json();
        }
    };

    const state = { items: [], sort: 'name', q: '', loading: false };

    function renderRows(items) {
        const tbody = $('#productsTbody');
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 24px;">No products</td></tr>';
            return;
        }
        tbody.innerHTML = '';
        for (const it of items) {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><button class="link-btn" data-edit="${it.pid}" aria-label="Edit ${it.name}">${it.name}</button></td>
                <td>${it.sku || ''}</td>
                <td class="num">
                    <div class="qty-inline">
                        <button class="icon-btn" data-adj="-1" data-id="${it.pid}" aria-label="Decrease">−</button>
                        <span class="qty" aria-live="polite">${it.stock}</span>
                        <button class="icon-btn" data-adj="1" data-id="${it.pid}" aria-label="Increase">+</button>
                    </div>
                </td>
                <td class="num">$${Number(it.price).toFixed(2)}</td>
                <td>
                    <button class="secondary-btn" data-edit="${it.pid}">Edit</button>
                    <button class="danger-btn" data-del="${it.pid}">Delete</button>
                </td>`;
            tbody.appendChild(tr);
        }
    }

    async function load() {
        state.loading = true;
        const { success, items } = await api.list(state.q, state.sort);
        state.loading = false;
        state.items = items || [];
        renderRows(state.items);
    }

    function readForm() {
        return {
            name: $('#name').value.trim(),
            sku: $('#sku').value.trim(),
            stock: Number($('#stock').value || 0),
            price: Number($('#price').value || 0),
            notes: $('#notes').value.trim()
        };
    }

    function setForm(data = {}) {
        $('#productId').value = data.pid || '';
        $('#name').value = data.name || '';
        $('#sku').value = data.sku || '';
        $('#stock').value = data.stock != null ? data.stock : '';
        $('#price').value = data.price != null ? data.price : '';
        $('#notes').value = data.notes || '';
        $('#modalTitle').textContent = data.pid ? 'Edit Product' : 'Add Product';
    }

    function clearErrors() {
        ['name','sku','stock','price'].forEach(f => {
            $(`#${f}Error`).textContent = '';
        });
    }

    function showErrors(errors) {
        Object.entries(errors).forEach(([k,v]) => {
            const el = $(`#${k}Error`);
            if (el) el.textContent = v;
        });
    }

    async function onSubmit(evt) {
        evt.preventDefault();
        clearErrors();
        const id = $('#productId').value;
        const data = readForm();
        const isUpdate = !!id;
        $('#saveBtn').disabled = true;
        const btnText = $('#saveBtn').textContent;
        $('#saveBtn').textContent = 'Saving...';

        // Optimistic: none, validate client-side first
        const errors = {};
        if (!data.name) errors.name = 'Name is required';
        if (data.stock < 0 || !Number.isFinite(data.stock)) errors.stock = 'Qty must be >= 0';
        if (data.price < 0 || !Number.isFinite(data.price)) errors.price = 'Price must be >= 0';
        if (Object.keys(errors).length) { showErrors(errors); $('#saveBtn').disabled = false; $('#saveBtn').textContent = btnText; return; }

        const res = isUpdate ? await api.update(id, data) : await api.create(data);
        $('#saveBtn').disabled = false;
        $('#saveBtn').textContent = btnText;
        if (!res.success) { if (res.errors) showErrors(res.errors); toast(res.message || 'Save failed', 'error'); return; }
        toast('Saved', 'success');
        setForm({});
        await load();
    }

    function wireEvents() {
        $('#productForm').addEventListener('submit', onSubmit);
        $('#resetBtn').addEventListener('click', () => { setForm({}); clearErrors(); $('#name').focus(); });
        $('#addProductBtn').addEventListener('click', () => { setForm({}); clearErrors(); $('#name').focus(); });
        $('#searchInputStock').addEventListener('input', (e) => { state.q = e.target.value; load(); });
        $('#sortSelect').addEventListener('change', (e) => { state.sort = e.target.value; load(); });

        $('#stock').addEventListener('keydown', (e) => { if (e.key === 'Enter') e.preventDefault(); });
        $$('.qty-btn').forEach(btn => btn.addEventListener('click', (e) => {
            const delta = Number(e.currentTarget.dataset.delta);
            const input = $('#stock');
            const val = Number(input.value || 0) + delta;
            input.value = Math.max(0, val);
        }));

        $('#productsTbody').addEventListener('click', async (e) => {
            const t = e.target.closest('button');
            if (!t) return;
            if (t.dataset.edit) {
                const id = Number(t.dataset.edit);
                const it = state.items.find(x => x.pid === id);
                if (it) { setForm(it); $('#name').focus(); }
                return;
            }
            if (t.dataset.adj) {
                const delta = Number(t.dataset.adj);
                const id = Number(t.dataset.id);
                const row = t.closest('tr');
                const qtyEl = row.querySelector('.qty');
                const prev = Number(qtyEl.textContent);
                const next = Math.max(0, prev + delta);
                qtyEl.textContent = String(next);
                const res = await api.adjust(id, delta);
                if (!res.success) { qtyEl.textContent = String(prev); toast('Adjust failed', 'error'); }
                else { toast('Quantity updated', 'success'); }
                return;
            }
            if (t.dataset.del) {
                const id = Number(t.dataset.del);
                if (!confirm('Delete this product?')) return;
                const res = await api.remove(id);
                if (!res.success) { toast('Delete failed', 'error'); return; }
                toast('Deleted', 'success');
                await load();
                return;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', async () => {
        wireEvents();
        await load();
        $('#searchInputStock').focus();
    });
})();


