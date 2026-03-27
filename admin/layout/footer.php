  <!-- Content Wrapper. Contains page content -->
  </div>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <strong>Copyright &copy; 2026 <a href="#">Gym Management</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0.0
    </div>
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<!-- Select2 -->
<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.min.js"></script>

<!-- Page specific script -->
<script>
  $(function () {
    function initAdminTable(selector) {
      if (!$(selector).length) {
        return;
      }

      if ($.fn.DataTable.isDataTable(selector)) {
        return $(selector).DataTable();
      }

      return $(selector).DataTable({
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        pageLength: 10,
        language: {
          search: 'Tìm kiếm:',
          lengthMenu: 'Hiển thị _MENU_ dòng',
          info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
          infoEmpty: 'Không có dữ liệu',
          zeroRecords: 'Không tìm thấy dữ liệu phù hợp',
          paginate: {
            first: 'Đầu',
            last: 'Cuối',
            next: 'Tiếp',
            previous: 'Trước'
          }
        }
      });
    }

    // Initialize DataTables for all tables
    $("#memberTable, #tierTable, #packageTable, #bmiTable, .data-table, .js-admin-table").each(function () {
      initAdminTable(this);
    });

    function filterPlainTable(tableSelector, keyword) {
      var $table = $(tableSelector);
      if (!$table.length) {
        return;
      }

      var normalizedKeyword = (keyword || '').toLowerCase().trim();
      $table.find('tbody tr').each(function () {
        var rowText = $(this).text().toLowerCase();
        $(this).toggle(normalizedKeyword === '' || rowText.indexOf(normalizedKeyword) !== -1);
      });
    }

    $(document).on('submit', '.js-table-filter-form', function (event) {
      event.preventDefault();

      var $form = $(this);
      var tableSelector = $form.data('filterTable');
      var inputSelector = $form.data('filterInput');
      var keyword = inputSelector ? $form.find(inputSelector).val() : $form.find('.js-table-filter-input').val();

      if (!tableSelector) {
        return;
      }

      if ($.fn.DataTable.isDataTable(tableSelector)) {
        $(tableSelector).DataTable().search(keyword || '').draw();
      } else {
        filterPlainTable(tableSelector, keyword || '');
      }
    });

    $(document).on('click', '.js-table-filter-reset', function (event) {
      event.preventDefault();

      var $form = $(this).closest('.js-table-filter-form');
      var tableSelector = $form.data('filterTable');
      var inputSelector = $form.data('filterInput');
      var $input = inputSelector ? $form.find(inputSelector) : $form.find('.js-table-filter-input');

      if ($input.length) {
        $input.val('');
      }

      if (!tableSelector) {
        return;
      }

      if ($.fn.DataTable.isDataTable(tableSelector)) {
        $(tableSelector).DataTable().search('').draw();
      } else {
        filterPlainTable(tableSelector, '');
      }
    });

    if ($.fn.select2) {
      $('.select2, .select2bs4').select2({
        theme: 'bootstrap4',
        width: '100%'
      });
    }
  });
</script>

</body>
</html>