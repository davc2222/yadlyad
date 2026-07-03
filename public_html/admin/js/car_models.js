function openModelModal(id = '', makerId = '', name = '', sortOrder = 0, isActive = 1) {
    document.getElementById('modelModalTitle').innerText = id ? 'עריכת דגם' : 'הוספת דגם';

    document.getElementById('modelId').value = id;
    document.getElementById('modelMakerId').value = makerId;
    document.getElementById('modelName').value = name;
    document.getElementById('modelSortOrder').value = sortOrder;
    document.getElementById('modelIsActive').checked = Number(isActive) === 1;

    document.getElementById('modelModal').style.display = 'flex';
}

function closeModelModal() {
    document.getElementById('modelModal').style.display = 'none';
}

function deleteModel(id) {
    if (!confirm('למחוק את הדגם?')) return;

    fetch('ajax/car_model_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id)
    })
        .then(res => res.text())
        .then(response => {
            if (response.trim() === 'OK') {
                location.reload();
            } else {
                alert(response);
            }
        });
}

document.getElementById('modelForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('ajax/car_model_save.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.text())
        .then(response => {
            if (response.trim() === 'OK') {
                location.reload();
            } else {
                alert(response);
            }
        });
});