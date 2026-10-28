<?php
require_once __DIR__ . '/../../config/session.php';
require_login();
$pageTitle = 'Product Store';

$totalProducts = (int)$pdo->query("SELECT COUNT(*) c FROM products")->fetch()['c'];
$stockValue    = (float)$pdo->query("SELECT COALESCE(SUM(price*stock),0) v FROM products")->fetch()['v'];
$lowStock      = (int)$pdo->query("SELECT COUNT(*) c FROM products WHERE stock <= 10")->fetch()['c'];
$monthSales    = (float)$pdo->query("SELECT COALESCE(SUM(total),0) v FROM product_sales WHERE MONTH(sale_date)=MONTH(CURDATE()) AND YEAR(sale_date)=YEAR(CURDATE())")->fetch()['v'];

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ===== Stat Cards ===== -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card purple fade-in-up"><i class="fa-solid fa-boxes-stacked"></i><p>Total Products</p><h3><?php echo number_format($totalProducts); ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card cyan fade-in-up"><i class="fa-solid fa-warehouse"></i><p>Stock Value</p><h3>Rs <?php echo number_format($stockValue); ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card pink fade-in-up"><i class="fa-solid fa-triangle-exclamation"></i><p>Low Stock (&le;10)</p><h3><?php echo number_format($lowStock); ?></h3></div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card green fade-in-up"><i class="fa-solid fa-cart-shopping"></i><p>Sales This Month</p><h3>Rs <?php echo number_format($monthSales); ?></h3></div>
  </div>
</div>

<!-- ===== Products Card ===== -->
<div class="card card-premium mb-4">
  <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
    <span><i class="fa-solid fa-boxes-stacked me-2"></i>Products</span>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-gradient btn-sm" id="btnRecordSale"><i class="fa-solid fa-cash-register me-1"></i> Record Sale</button>
      <button class="btn btn-light btn-sm fw-600" id="btnAddProduct"><i class="fa-solid fa-plus me-1"></i> Add Product</button>
    </div>
  </div>
  <div class="card-body">
    <table id="productTable" class="table table-hover align-middle" style="width:100%">
      <thead>
        <tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th class="no-print">Actions</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- ===== Recent Sales Card ===== -->
<div class="card card-premium">
  <div class="card-header"><i class="fa-solid fa-receipt me-2"></i>Recent Sales</div>
  <div class="card-body">
    <table id="salesTable" class="table table-hover align-middle" style="width:100%">
      <thead>
        <tr><th>Date</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Buyer</th><th class="no-print">Actions</th></tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- ===== Add / Edit Product Modal ===== -->
<div class="modal fade" id="productModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:16px; border:none;">
      <div class="modal-header" style="background:linear-gradient(90deg,#7c3aed,#ec4899); color:#fff;">
        <h5 class="modal-title" id="productModalTitle"><i class="fa-solid fa-plus me-2"></i>Add Product</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="productForm">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="id" id="p_id">
          <div class="text-center mb-3">
            <img id="productPreviewImg" src="<?php echo BASE_URL; ?>assets/img/default-product.png" class="product-thumb-lg mb-2">
            <div>
              <label class="btn btn-outline-gradient btn-sm">
                <i class="fa-solid fa-camera me-1"></i> Upload Image
                <input type="file" name="image" id="p_image" accept="image/*" hidden>
              </label>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Product Name *</label>
              <input type="text" class="form-control" name="name" id="p_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <input type="text" class="form-control" name="category" id="p_category" placeholder="e.g. Equipment, Nutrition, Apparel">
            </div>
            <div class="col-md-6">
              <label class="form-label">Price (Rs) *</label>
              <input type="number" step="0.01" min="0" class="form-control" name="price" id="p_price" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Stock Quantity *</label>
              <input type="number" min="0" class="form-control" name="stock" id="p_stock" required>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-gradient" id="saveProductBtn"><i class="fa-solid fa-floppy-disk me-1"></i> Save Product</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Record Sale Modal ===== -->
<div class="modal fade" id="saleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:16px; border:none;">
      <div class="modal-header" style="background:linear-gradient(90deg,#10B981,#06B6D4); color:#fff;">
        <h5 class="modal-title"><i class="fa-solid fa-cash-register me-2"></i>Record Sale</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="saleForm">
          <?php echo csrf_field(); ?>
          <div class="mb-3">
            <label class="form-label">Product *</label>
            <select class="form-select" name="product_id" id="s_product_id" required></select>
            <div class="form-text" id="s_stock_hint"></div>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Quantity *</label>
              <input type="number" min="1" value="1" class="form-control" name="quantity" id="s_quantity" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Sale Date *</label>
              <input type="date" class="form-control" name="sale_date" id="s_date" required>
            </div>
            <div class="col-12">
              <label class="form-label">Buyer (optional — leave blank for walk-in)</label>
              <select class="form-select" name="member_id" id="s_member_id">
                <option value="">— Walk-in Customer —</option>
              </select>
            </div>
          </div>
          <div class="alert alert-light border mt-3 mb-0 d-flex justify-content-between">
            <span class="fw-600">Total Amount</span>
            <span class="fw-700" id="s_total">Rs 0.00</span>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-gradient" id="confirmSaleBtn"><i class="fa-solid fa-check me-1"></i> Confirm Sale</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const productModal = new bootstrap.Modal(document.getElementById('productModal'));
  const saleModal = new bootstrap.Modal(document.getElementById('saleModal'));
  let productTable, salesTable, productsCache = [];

  // ===== Load Products Table =====
  function loadProducts() {
    $.getJSON(BASE_URL + 'ajax/store/list.php', function (res) {
      if (!res.success) return;
      productsCache = res.data;
      if (productTable) { productTable.destroy(); $('#productTable').empty().html('<thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th class="no-print">Actions</th></tr></thead>'); }
      productTable = $('#productTable').DataTable({
        data: res.data, responsive: true, pageLength: 10,
        columns: [
          { data: 'image_url', orderable: false, render: d => `<img src="${d}" class="product-thumb">` },
          { data: 'name' },
          { data: 'category', render: d => d || '—' },
          { data: 'price', render: d => 'Rs ' + parseFloat(d).toLocaleString(undefined, {minimumFractionDigits:2}) },
          { data: 'stock', render: d => `<span class="${d <= 10 ? 'stock-low' : 'stock-ok'}">${d}</span>` },
          { data: null, orderable: false, className: 'no-print', render: d => `
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-gradient btn-edit-product" data-id="${d.id}" title="Edit"><i class="fa-solid fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-delete-product" data-id="${d.id}" title="Delete"><i class="fa-solid fa-trash"></i></button>
              </div>` }
        ]
      });
    });
  }
  loadProducts();

  // ===== Load Sales Table =====
  function loadSales() {
    $.getJSON(BASE_URL + 'ajax/store/sales_list.php', function (res) {
      if (!res.success) return;
      if (salesTable) { salesTable.destroy(); $('#salesTable').empty().html('<thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Buyer</th><th class="no-print">Actions</th></tr></thead>'); }
      salesTable = $('#salesTable').DataTable({
        data: res.data, responsive: true, pageLength: 10, order: [[0, 'desc']],
        columns: [
          { data: 'sale_date' },
          { data: 'product_name' },
          { data: 'quantity' },
          { data: 'unit_price', render: d => 'Rs ' + parseFloat(d).toLocaleString(undefined, {minimumFractionDigits:2}) },
          { data: 'total', render: d => 'Rs ' + parseFloat(d).toLocaleString(undefined, {minimumFractionDigits:2}) },
          { data: null, render: d => d.member_name ? `${d.member_name} <span class="text-muted small">(${d.member_code})</span>` : 'Walk-in' },
          { data: null, orderable: false, className: 'no-print', render: d => `
              <div class="d-flex gap-1">
                <a href="${BASE_URL}modules/store/print_invoice.php?id=${d.id}" target="_blank" class="btn btn-sm btn-outline-gradient" title="Print Invoice"><i class="fa-solid fa-print"></i></a>
                <button class="btn btn-sm btn-outline-danger btn-delete-sale" data-id="${d.id}" title="Delete & Restock"><i class="fa-solid fa-trash"></i></button>
              </div>` }
        ]
      });
    });
  }
  loadSales();

  // ===== Add Product =====
  $('#btnAddProduct').on('click', function () {
    $('#productForm')[0].reset();
    $('#p_id').val('');
    $('#productModalTitle').html('<i class="fa-solid fa-plus me-2"></i>Add Product');
    $('#productPreviewImg').attr('src', BASE_URL + 'assets/img/default-product.png');
    productModal.show();
  });

  $(document).on('change', '#p_image', function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => $('#productPreviewImg').attr('src', e.target.result);
      reader.readAsDataURL(file);
    }
  });

  // ===== Edit Product =====
  $('#productTable').on('click', '.btn-edit-product', function () {
    const id = $(this).data('id');
    $.getJSON(BASE_URL + 'ajax/store/get.php', { id }, function (res) {
      if (!res.success) return Swal.fire('Error', res.message, 'error');
      const p = res.product;
      $('#productModalTitle').html('<i class="fa-solid fa-pen me-2"></i>Edit Product');
      $('#p_id').val(p.id);
      $('#p_name').val(p.name);
      $('#p_category').val(p.category);
      $('#p_price').val(p.price);
      $('#p_stock').val(p.stock);
      $('#productPreviewImg').attr('src', p.image_url);
      productModal.show();
    });
  });

  // ===== Save Product =====
  $('#saveProductBtn').on('click', function () {
    const form = $('#productForm')[0];
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const formData = new FormData(form);
    const btn = $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...');

    $.ajax({
      url: BASE_URL + 'ajax/store/save.php', type: 'POST', data: formData,
      processData: false, contentType: false, dataType: 'json',
    }).done(function (res) {
      if (res.success) {
        productModal.hide();
        Swal.fire({ icon: 'success', title: 'Success', text: res.message, timer: 2000, showConfirmButton: false });
        loadProducts();
      } else {
        Swal.fire('Error', res.message || 'Something went wrong.', 'error');
      }
    }).fail(function () {
      Swal.fire('Error', 'Server error while saving product.', 'error');
    }).always(function () {
      btn.prop('disabled', false).html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Product');
    });
  });

  // ===== Delete Product =====
  $('#productTable').on('click', '.btn-delete-product', function () {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Delete this product?', text: 'This will also remove its sales history.', icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, delete',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(BASE_URL + 'ajax/store/delete.php', { id: id, csrf_token: window.CSRF_TOKEN }, function (res) {
          if (res.success) { Swal.fire('Deleted!', res.message, 'success'); loadProducts(); loadSales(); }
          else Swal.fire('Error', res.message, 'error');
        }, 'json');
      }
    });
  });

  // ===== Record Sale Modal =====
  $('#btnRecordSale').on('click', function () {
    $('#saleForm')[0].reset();
    $('#s_date').val(new Date().toISOString().slice(0, 10));
    const $p = $('#s_product_id').empty();
    productsCache.forEach(p => $p.append(`<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock}">${p.name} (Rs ${parseFloat(p.price).toFixed(2)})</option>`));
    updateSaleTotal();

    $.getJSON(BASE_URL + 'ajax/store/lookups.php', function (res) {
      const $m = $('#s_member_id');
      $m.find('option:not(:first)').remove();
      res.members.forEach(m => $m.append(`<option value="${m.id}">${m.full_name} (${m.member_code})</option>`));
    });

    saleModal.show();
  });

  function updateSaleTotal() {
    const opt = $('#s_product_id option:selected');
    const price = parseFloat(opt.data('price')) || 0;
    const stock = parseInt(opt.data('stock')) || 0;
    const qty = parseInt($('#s_quantity').val()) || 0;
    $('#s_stock_hint').text(stock ? `${stock} unit(s) in stock` : 'Out of stock');
    $('#s_total').text('Rs ' + (price * qty).toLocaleString(undefined, {minimumFractionDigits:2}));
  }
  $(document).on('change', '#s_product_id, #s_quantity', updateSaleTotal);

  $('#confirmSaleBtn').on('click', function () {
    const form = $('#saleForm')[0];
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const btn = $(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...');

    $.post(BASE_URL + 'ajax/store/sell.php', $(form).serialize(), function (res) {
      if (res.success) {
        saleModal.hide();
        Swal.fire({ icon: 'success', title: 'Sale Recorded', text: res.message, timer: 2000, showConfirmButton: false });
        loadProducts(); loadSales();
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    }, 'json').always(function () {
      btn.prop('disabled', false).html('<i class="fa-solid fa-check me-1"></i> Confirm Sale');
    });
  });

  // ===== Delete Sale =====
  $('#salesTable').on('click', '.btn-delete-sale', function () {
    const id = $(this).data('id');
    Swal.fire({
      title: 'Remove this sale?', text: 'The sold quantity will be added back to stock.', icon: 'warning',
      showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Yes, remove',
    }).then((result) => {
      if (result.isConfirmed) {
        $.post(BASE_URL + 'ajax/store/sale_delete.php', { id: id, csrf_token: window.CSRF_TOKEN }, function (res) {
          if (res.success) { Swal.fire('Removed!', res.message, 'success'); loadProducts(); loadSales(); }
          else Swal.fire('Error', res.message, 'error');
        }, 'json');
      }
    });
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
