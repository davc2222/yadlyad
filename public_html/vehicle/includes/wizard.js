document.addEventListener('DOMContentLoaded', function () {
    const manufacturerSelect = document.getElementById('manufacturerSelect');
    const modelSelect = document.getElementById('modelSelect');

    if (manufacturerSelect && modelSelect) {
        manufacturerSelect.addEventListener('change', function () {
            const makerId = this.value;

            modelSelect.innerHTML = '<option value="">טוען דגמים...</option>';

            if (!makerId) {
                modelSelect.innerHTML = '<option value="">בחר קודם יצרן</option>';
                return;
            }

            fetch('/ajax/get_car_models.php?maker_id=' + encodeURIComponent(makerId))
                .then(response => response.json())
                .then(models => {
                    modelSelect.innerHTML = '<option value="">בחר דגם</option>';

                    if (!models || models.length === 0) {
                        modelSelect.innerHTML = '<option value="">אין דגמים ליצרן זה</option>';
                        return;
                    }

                    models.forEach(function (model) {
                        const option = document.createElement('option');
                        option.value = model.id;
                        option.textContent = model.name;
                        modelSelect.appendChild(option);
                    });
                })
                .catch(function () {
                    modelSelect.innerHTML = '<option value="">שגיאה בטעינת דגמים</option>';
                });
        });
    }
});