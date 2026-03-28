<?php
$filterTitle = $filterTitle ?? 'Lọc dữ liệu';
$filterPlaceholder = $filterPlaceholder ?? 'Nhập từ khóa...';
$filterHelpText = $filterHelpText ?? '';
$filterValue = $filterValue ?? '';
$filterTableSelector = $filterTableSelector ?? '.js-admin-table';
$filterFormId = $filterFormId ?? 'filterForm';
$filterFieldsHtml = $filterFieldsHtml ?? '';
$filterAction = $filterAction ?? '';
$filterMethod = $filterMethod ?? 'GET';
$filterMode = $filterMode ?? 'client';
?>

<div class="row mb-3">
  <div class="col-12">
    <div class="card card-primary collapsed-card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> <?= htmlspecialchars($filterTitle) ?></h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-plus"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <form method="<?= htmlspecialchars($filterMethod) ?>" action="<?= htmlspecialchars($filterAction) ?>" class="<?= $filterMode === 'server' ? '' : 'js-table-filter-form' ?>" id="<?= htmlspecialchars($filterFormId) ?>"<?= $filterMode === 'server' ? '' : ' data-filter-table="' . htmlspecialchars($filterTableSelector) . '"' ?>>
          <div class="row align-items-end">
            <?php if (!empty($filterFieldsHtml)): ?>
              <?= $filterFieldsHtml ?>
            <?php else: ?>
              <div class="col-md-10">
                <div class="form-group mb-0">
                  <label>Từ khóa</label>
                  <input type="text" class="form-control js-table-filter-input" placeholder="<?= htmlspecialchars($filterPlaceholder) ?>" value="<?= htmlspecialchars($filterValue) ?>">
                  <?php if (!empty($filterHelpText)): ?>
                    <small class="form-text text-muted"><?= htmlspecialchars($filterHelpText) ?></small>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>
            <div class="col-md-2">
              <div class="form-group mb-0">
                <button type="submit" class="btn btn-primary btn-block mb-2">
                  <i class="fas fa-search"></i> Lọc
                </button>
                <?php if ($filterMode === 'server'): ?>
                  <a href="<?= htmlspecialchars($filterAction) ?>" class="btn btn-secondary btn-block">
                    <i class="fas fa-redo"></i> Xóa bộ lọc
                  </a>
                <?php else: ?>
                  <button type="button" class="btn btn-secondary btn-block js-table-filter-reset">
                    <i class="fas fa-redo"></i> Xóa bộ lọc
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
