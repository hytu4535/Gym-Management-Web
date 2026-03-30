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
    if (!document.getElementById('admin-inline-validation-style')) {
      var validationStyle = document.createElement('style');
      validationStyle.id = 'admin-inline-validation-style';
      validationStyle.textContent = [
        '.admin-inline-error{display:block;font-size:.875rem;color:#dc3545;margin-top:.25rem;}',
        '.select2-container--bootstrap4.select2-container--focus .select2-selection.is-invalid,',
        '.select2-container--bootstrap4 .select2-selection.is-invalid{border-color:#dc3545;box-shadow:0 0 0 .2rem rgba(220,53,69,.15);}',
        '.select2-container--open .select2-dropdown{z-index:2055;}',
        '.select2-container--open .select2-search--dropdown{padding:8px;pointer-events:auto;}',
        '.select2-container--open .select2-search__field{width:100% !important;height:34px;pointer-events:auto;}',
        '.select2-results__options{max-height:260px;overflow-y:auto;}'
      ].join('');
      document.head.appendChild(validationStyle);
    }

    function initAdminTable(selector) {
      if (!$(selector).length) {
        return;
      }

      var tableId = $(selector).attr('id') || '';
      var disableLengthMenu = tableId === 'memberTable' || tableId === 'packageTable';

      if ($.fn.DataTable.isDataTable(selector)) {
        return $(selector).DataTable();
      }

      return $(selector).DataTable({
        responsive: true,
        lengthChange: !disableLengthMenu,
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
      function normalizeVietnamese(text) {
        var source = String(text || '').toLowerCase().trim();
        if (typeof source.normalize === 'function') {
          source = source.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return source.replace(/đ/g, 'd');
      }

      function buildSearchMatcher(params, data) {
        var keyword = normalizeVietnamese(params.term || '');
        if (!keyword) {
          return data;
        }

        var optionText = normalizeVietnamese(data && data.text ? data.text : '');
        if (optionText.indexOf(keyword) !== -1) {
          return data;
        }

        return null;
      }

      function detectPlaceholder($select) {
        if ($select.data('placeholder')) {
          return $select.data('placeholder');
        }

        var firstOption = $select.find('option').first();
        if (firstOption.length && (firstOption.attr('value') || '') === '') {
          return firstOption.text();
        }

        return 'Nhập để tìm...';
      }

      function shouldEnableSearchableSelect($select) {
        if ($select.data('select2') || $select.hasClass('select2-hidden-accessible')) {
          return false;
        }

        if ($select.prop('disabled')) {
          return false;
        }

        var isVisible = $select.is(':visible');
        var insideVisibleModal = $select.closest('.modal.show').length > 0;
        if (!isVisible && !insideVisibleModal) {
          return false;
        }

        if ($select.data('hideSearch') === true || String($select.data('hideSearch')) === 'true') {
          return false;
        }

        if ($select.hasClass('select2') || $select.hasClass('select2bs4') || $select.hasClass('js-searchable-select')) {
          return true;
        }

        var optionsCount = $select.find('option').length;
        var identity = (($select.attr('name') || '') + ' ' + ($select.attr('id') || '')).toLowerCase();
        var forceSearchByField = /(member|user|account|email|package|pkg|equipment|machine|device|trainer|class|service|nutrition|plan|supplier|product)/.test(identity);
        var isSimpleStatus = /(status|state|trang_thai|trangthai)$/.test(identity);

        if (forceSearchByField && !isSimpleStatus) {
          return true;
        }

        return optionsCount >= 5 && !isSimpleStatus;
      }

      function initSearchableSelect($select) {
        if (!shouldEnableSearchableSelect($select)) {
          return;
        }

        var config = {
          theme: 'bootstrap4',
          width: '100%',
          matcher: buildSearchMatcher,
          placeholder: detectPlaceholder($select),
          allowClear: ($select.find('option[value=""]').length > 0)
        };

        var $modal = $select.closest('.modal');
        if ($modal.length) {
          config.dropdownParent = $modal;
        }

        $select.select2(config);
      }

      function initSearchableSelects(scope) {
        var $scope = scope ? $(scope) : $(document);
        $scope.find('select').each(function () {
          initSearchableSelect($(this));
        });
      }

      initSearchableSelects(document);

      $(document).on('shown.bs.modal', '.modal', function () {
        initSearchableSelects(this);
      });

      $(document).on('shown.bs.tab shown.bs.collapse', function (event) {
        initSearchableSelects(event.target);
      });

      if (window.MutationObserver) {
        var selectObserver = new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            if (!mutation.addedNodes || !mutation.addedNodes.length) {
              return;
            }

            Array.prototype.forEach.call(mutation.addedNodes, function (node) {
              if (!node || node.nodeType !== 1) {
                return;
              }

              if (node.tagName === 'SELECT') {
                initSearchableSelect($(node));
                return;
              }

              if (node.querySelectorAll) {
                initSearchableSelects(node);
              }
            });
          });
        });

        selectObserver.observe(document.body, {
          childList: true,
          subtree: true
        });
      }

      $(document).on('select2:open', function () {
        window.setTimeout(function () {
          var searchInput = document.querySelector('.select2-container--open .select2-search__field');
          if (searchInput) {
            searchInput.disabled = false;
            searchInput.readOnly = false;
            searchInput.style.pointerEvents = 'auto';
            searchInput.focus();
          }
        }, 0);
      });
    }

    var forms = Array.prototype.slice.call(document.querySelectorAll('form[method="POST"], form[method="post"]'));

    function getFieldKey(field) {
      var key = field.id || field.name || 'field';
      var form = field.form;
      if (form && form.id) {
        key = form.id + '__' + key;
      }
      return key;
    }

    function getFieldLabel(field) {
      var id = field.getAttribute('id');
      if (id) {
        var label = document.querySelector('label[for="' + id + '"]');
        if (label) {
          return (label.textContent || '').replace(/\*/g, '').trim();
        }
      }

      var group = field.closest('.form-group, .form-row, .form-col, .col, .mb-3, .mb-2');
      if (group) {
        var localLabel = group.querySelector('label');
        if (localLabel) {
          return (localLabel.textContent || '').replace(/\*/g, '').trim();
        }
      }

      return field.getAttribute('placeholder') || field.getAttribute('name') || 'Trường này';
    }

    function getErrorNode(field) {
      var fieldKey = getFieldKey(field);
      var formScope = field.form || document;
      var existing = formScope.querySelector('.admin-inline-error[data-for="' + fieldKey + '"]');
      if (existing) {
        return existing;
      }

      var node = document.createElement('div');
      node.className = 'invalid-feedback admin-inline-error';
      node.dataset.for = fieldKey;

      if (field.classList.contains('select2-hidden-accessible')) {
        var select2Container = field.nextElementSibling;
        if (select2Container && select2Container.classList.contains('select2')) {
          var selection = select2Container.querySelector('.select2-selection');
          if (selection) {
            selection.classList.add('is-invalid');
          }
          select2Container.insertAdjacentElement('afterend', node);
          return node;
        }
      }

      if (field.parentNode) {
        field.insertAdjacentElement('afterend', node);
      }
      return node;
    }

    function clearFieldError(field) {
      field.classList.remove('is-invalid');
      field.removeAttribute('aria-invalid');

      var select2Container = field.nextElementSibling;
      if (field.classList.contains('select2-hidden-accessible') && select2Container && select2Container.classList.contains('select2')) {
        var selection = select2Container.querySelector('.select2-selection');
        if (selection) {
          selection.classList.remove('is-invalid');
        }
      }

      var scope = field.form || document;
      var fieldKey = getFieldKey(field);
      var errors = scope.querySelectorAll('.admin-inline-error[data-for="' + fieldKey + '"]');
      errors.forEach(function (errorNode) {
        errorNode.textContent = '';
        errorNode.style.display = 'none';
      });

      var wrapper = field.closest('.form-group, .form-row, .form-col, .col, .mb-3, .mb-2') || field.parentNode;
      if (wrapper && wrapper.querySelectorAll) {
        wrapper.querySelectorAll('.invalid-feedback.custom-error-text').forEach(function (customError) {
          customError.style.display = 'none';
        });
      }
    }

    function showFieldError(field, message) {
      field.classList.add('is-invalid');
      field.setAttribute('aria-invalid', 'true');
      var errorNode = getErrorNode(field);
      errorNode.textContent = message;
      errorNode.style.display = 'block';
    }

    function isFieldRequired(field) {
      return field.required || field.dataset.required === 'true' || field.getAttribute('aria-required') === 'true';
    }

    function getValidationMessage(field) {
      var value = (field.value || '').trim();
      var label = getFieldLabel(field);
      var type = (field.getAttribute('type') || '').toLowerCase();
      var fieldName = (field.getAttribute('name') || '').toLowerCase();

      if (isFieldRequired(field) && value === '') {
        return label + ' không được để trống.';
      }

      if (value === '') {
        return '';
      }

      if (type === 'email' || field.getAttribute('inputmode') === 'email' || fieldName.indexOf('email') !== -1) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
          return label + ' không đúng định dạng email.';
        }
      }

      if (type === 'tel' || fieldName.indexOf('phone') !== -1 || fieldName.indexOf('tel') !== -1) {
        var phoneRegex = /^(0|\+84)\d{8,10}$/;
        if (!phoneRegex.test(value.replace(/[\.\s-]/g, ''))) {
          return label + ' không đúng định dạng số điện thoại.';
        }
      }

      if (type === 'number') {
        var numberValue = Number(value);
        if (Number.isNaN(numberValue)) {
          return label + ' phải là số hợp lệ.';
        }

        if (field.min !== '' && numberValue < Number(field.min)) {
          return label + ' phải lớn hơn hoặc bằng ' + field.min + '.';
        }

        if (field.max !== '' && numberValue > Number(field.max)) {
          return label + ' phải nhỏ hơn hoặc bằng ' + field.max + '.';
        }
      }

      if (type === 'date') {
        if (field.min && value < field.min) {
          return label + ' không được nhỏ hơn ' + field.min + '.';
        }
        if (field.max && value > field.max) {
          return label + ' không được lớn hơn ' + field.max + '.';
        }
      }

      if (field.hasAttribute('pattern')) {
        try {
          var pattern = new RegExp('^(?:' + field.getAttribute('pattern') + ')$');
          if (!pattern.test(value)) {
            return label + ' không đúng định dạng.';
          }
        } catch (error) {
          return '';
        }
      }

      return '';
    }

    function getFormFields(form) {
      return Array.prototype.slice.call(form.querySelectorAll('input, select, textarea')).filter(function (field) {
        if (!field.name || field.disabled) {
          return false;
        }

        var type = (field.getAttribute('type') || '').toLowerCase();
        if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset') {
          return false;
        }

        if (field.closest('[data-skip-inline-validation="true"]')) {
          return false;
        }

        return true;
      });
    }

    function validateField(field) {
      var message = getValidationMessage(field);
      if (message) {
        showFieldError(field, message);
        return false;
      }

      clearFieldError(field);
      return true;
    }

    forms.forEach(function (form) {
      if (form.dataset.skipInlineValidation === 'true') {
        return;
      }

      form.setAttribute('novalidate', 'novalidate');

      form.addEventListener('submit', function (event) {
        var hasError = false;
        var firstInvalid = null;

        getFormFields(form).forEach(function (field) {
          if (!validateField(field)) {
            hasError = true;
            if (!firstInvalid) {
              firstInvalid = field;
            }
          }
        });

        if (hasError) {
          event.preventDefault();
          if (firstInvalid && typeof firstInvalid.focus === 'function') {
            firstInvalid.focus();
          }
        }
      });

      form.addEventListener('reset', function () {
        window.setTimeout(function () {
          getFormFields(form).forEach(function (field) {
            clearFieldError(field);
          });
        }, 0);
      });
    });

    $(document).on('input change blur', 'form[method="POST"] input, form[method="POST"] select, form[method="POST"] textarea, form[method="post"] input, form[method="post"] select, form[method="post"] textarea', function () {
      var field = this;
      if (!field || !field.form || field.form.dataset.skipInlineValidation === 'true') {
        return;
      }

      var type = (field.getAttribute('type') || '').toLowerCase();
      if (type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset') {
        return;
      }

      validateField(field);
    });

    $(document).on('select2:select select2:clear select2:close', 'select', function () {
      if (!this.form || (this.form.method || '').toLowerCase() !== 'post' || this.form.dataset.skipInlineValidation === 'true') {
        return;
      }
      validateField(this);
    });

    document.addEventListener('invalid', function (event) {
      var form = event.target && event.target.form;
      if (!form) {
        return;
      }

      if (form.dataset.skipInlineValidation === 'true') {
        return;
      }

      if ((form.method || '').toLowerCase() === 'post') {
        event.preventDefault();
      }
    }, true);
  });
</script>

</body>
</html>