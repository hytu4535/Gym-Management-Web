(function () {
  'use strict';

  var API_URL = 'https://provinces.open-api.vn/api/?depth=3';
  var addressDataPromise = null;

  function loadAddressData() {
    if (!addressDataPromise) {
      addressDataPromise = fetch(API_URL)
        .then(function (response) {
          if (!response.ok) {
            throw new Error('Cannot load address data');
          }
          return response.json();
        })
        .catch(function () {
          return [];
        });
    }

    return addressDataPromise;
  }

  function findOptionByValue(select, value) {
    var options = select.options;
    for (var i = 0; i < options.length; i++) {
      if (options[i].value === value) {
        return options[i];
      }
    }
    return null;
  }

  function setSelectOptions(select, items, placeholder, selectedValue) {
    if (!select) {
      return;
    }

    select.innerHTML = '';

    var placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.textContent = placeholder;
    select.appendChild(placeholderOption);

    items.forEach(function (item) {
      var option = document.createElement('option');
      option.value = item.value;
      option.textContent = item.label;
      if (item.code) {
        option.setAttribute('data-code', item.code);
      }
      select.appendChild(option);
    });

    if (selectedValue) {
      select.value = selectedValue;
    }
  }

  function getFormField(form, name) {
    return form.querySelector('[name="' + name + '"]');
  }

  function getErrorBox(form, name) {
    return form.querySelector('[data-error-for="' + name + '"]');
  }

  function clearError(form, name) {
    var field = getFormField(form, name);
    var errorBox = getErrorBox(form, name);
    if (field) {
      field.classList.remove('is-invalid');
    }
    if (errorBox) {
      errorBox.textContent = '';
      errorBox.classList.add('d-none');
    }
  }

  function setError(form, name, message) {
    var field = getFormField(form, name);
    var errorBox = getErrorBox(form, name);
    if (field) {
      field.classList.add('is-invalid');
    }
    if (errorBox) {
      errorBox.textContent = message;
      errorBox.classList.remove('d-none');
    }
  }

  function clearAllErrors(form) {
    ['member_id', 'full_address', 'city', 'district', 'ward', 'type'].forEach(function (name) {
      clearError(form, name);
    });
  }

  function getSelectedOptionData(select) {
    if (!select || select.selectedIndex < 0) {
      return null;
    }
    return select.options[select.selectedIndex];
  }

  function populateDistricts(form, provinceName, selectedDistrictName, selectedWardName) {
    var provinceSelect = getFormField(form, 'city');
    var districtSelect = getFormField(form, 'district');
    var wardSelect = getFormField(form, 'ward');

    if (!provinceSelect || !districtSelect || !wardSelect) {
      return Promise.resolve();
    }

    return loadAddressData().then(function (data) {
      var province = data.find(function (item) {
        return item.name === provinceName;
      });

      if (!province) {
        setSelectOptions(districtSelect, [], '-- Chọn Quận/Huyện --');
        setSelectOptions(wardSelect, [], '-- Chọn Phường/Xã --');
        return;
      }

      var districtItems = (province.districts || []).map(function (district) {
        return { value: district.name, label: district.name, code: district.code };
      });
      setSelectOptions(districtSelect, districtItems, '-- Chọn Quận/Huyện --', selectedDistrictName || '');

      var district = (province.districts || []).find(function (item) {
        return item.name === selectedDistrictName;
      });

      if (!district) {
        setSelectOptions(wardSelect, [], '-- Chọn Phường/Xã --');
        return;
      }

      var wardItems = (district.wards || []).map(function (ward) {
        return { value: ward.name, label: ward.name, code: ward.code };
      });
      setSelectOptions(wardSelect, wardItems, '-- Chọn Phường/Xã --', selectedWardName || '');
    });
  }

  function initForm(form) {
    if (!form || form.dataset.addressPickerInitialized === '1') {
      return;
    }
    form.dataset.addressPickerInitialized = '1';

    var provinceSelect = getFormField(form, 'city');
    var districtSelect = getFormField(form, 'district');
    var wardSelect = getFormField(form, 'ward');

    loadAddressData().then(function (data) {
      if (!provinceSelect || !districtSelect || !wardSelect) {
        return;
      }

      var provinceItems = data.map(function (item) {
        return { value: item.name, label: item.name, code: item.code };
      });
      setSelectOptions(provinceSelect, provinceItems, '-- Chọn Tỉnh/Thành phố --', provinceSelect.value || '');

      var selectedProvince = provinceSelect.value || form.dataset.initialProvince || '';
      var selectedDistrict = districtSelect.value || form.dataset.initialDistrict || '';
      var selectedWard = wardSelect.value || form.dataset.initialWard || '';

      if (selectedProvince) {
        provinceSelect.value = selectedProvince;
        populateDistricts(form, selectedProvince, selectedDistrict, selectedWard);
      } else {
        setSelectOptions(districtSelect, [], '-- Chọn Quận/Huyện --');
        setSelectOptions(wardSelect, [], '-- Chọn Phường/Xã --');
      }
    });

    if (provinceSelect) {
      provinceSelect.addEventListener('change', function () {
        clearError(form, 'city');
        clearError(form, 'district');
        clearError(form, 'ward');
        populateDistricts(form, provinceSelect.value, '', '');
      });
    }

    if (districtSelect) {
      districtSelect.addEventListener('change', function () {
        clearError(form, 'district');
        clearError(form, 'ward');
        populateDistricts(form, provinceSelect ? provinceSelect.value : '', districtSelect.value, '');
      });
    }

    if (wardSelect) {
      wardSelect.addEventListener('change', function () {
        clearError(form, 'ward');
      });
    }

    ['member_id', 'full_address', 'city', 'district', 'ward', 'type'].forEach(function (name) {
      var field = getFormField(form, name);
      if (field) {
        field.addEventListener('input', function () {
          clearError(form, name);
        });
        field.addEventListener('change', function () {
          clearError(form, name);
        });
      }
    });
  }

  function validateForm(form) {
    clearAllErrors(form);

    var isValid = true;
    var memberField = getFormField(form, 'member_id');
    var fullField = getFormField(form, 'full_address');
    var provinceField = getFormField(form, 'city');
    var districtField = getFormField(form, 'district');
    var wardField = getFormField(form, 'ward');
    var typeField = getFormField(form, 'type');

    if (memberField && !memberField.value) {
      setError(form, 'member_id', 'Vui lòng chọn hội viên.');
      isValid = false;
    }

    if (!fullField || !fullField.value.trim()) {
      setError(form, 'full_address', 'Vui lòng nhập địa chỉ chi tiết.');
      isValid = false;
    }

    if (!provinceField || !provinceField.value) {
      setError(form, 'city', 'Vui lòng chọn Tỉnh/Thành phố.');
      isValid = false;
    }

    if (!districtField || !districtField.value) {
      setError(form, 'district', 'Vui lòng chọn Quận/Huyện.');
      isValid = false;
    }

    if (!wardField || !wardField.value) {
      setError(form, 'ward', 'Vui lòng chọn Phường/Xã.');
      isValid = false;
    }

    if (typeField && !typeField.value) {
      setError(form, 'type', 'Vui lòng chọn loại địa chỉ.');
      isValid = false;
    }

    return isValid;
  }

  function fillForm(form, data) {
    if (!form || !data) {
      return;
    }

    var hiddenId = getFormField(form, 'id');
    var memberField = getFormField(form, 'member_id');
    var fullField = getFormField(form, 'full_address');
    var provinceField = getFormField(form, 'city');
    var districtField = getFormField(form, 'district');
    var wardField = getFormField(form, 'ward');
    var typeField = getFormField(form, 'type');
    var defaultField = getFormField(form, 'is_default');

    if (hiddenId) hiddenId.value = data.id || '';
    if (memberField && data.member_id) memberField.value = data.member_id;
    if (fullField) fullField.value = data.full_address || '';
    if (typeField) typeField.value = data.type || 'home';
    if (defaultField) defaultField.checked = String(data.is_default) === '1' || data.is_default === 1 || data.is_default === true;

    form.dataset.initialProvince = data.city || '';
    form.dataset.initialDistrict = data.district || '';
    form.dataset.initialWard = data.ward || '';
    form.dataset.initialType = data.type || 'home';

    if (provinceField) provinceField.value = data.city || '';
    if (districtField) districtField.value = data.district || '';
    if (wardField) wardField.value = data.ward || '';

    if (provinceField && data.city) {
      populateDistricts(form, data.city, data.district || '', data.ward || '');
    }
  }

  function resetForm(form) {
    if (!form) {
      return;
    }

    form.reset();
    clearAllErrors(form);
    form.dataset.initialProvince = '';
    form.dataset.initialDistrict = '';
    form.dataset.initialWard = '';
    form.dataset.initialType = 'home';
    var hiddenId = getFormField(form, 'id');
    if (hiddenId) hiddenId.value = '';
  }

  window.AddressPicker = {
    initForm: initForm,
    validateForm: validateForm,
    fillForm: fillForm,
    resetForm: resetForm,
    populateDistricts: populateDistricts
  };

  document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('[data-address-picker]');
    forms.forEach(function (form) {
      initForm(form);
    });
  });
})();
