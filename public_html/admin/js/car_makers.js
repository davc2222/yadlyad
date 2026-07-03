function openMakerModal(id = 0, name = '', sort = 0, active = 1) {
    document.getElementById('makerModal').style.display = 'flex';

    document.getElementById('makerId').value = id;
    document.getElementById('makerName').value = name;
    document.getElementById('makerSortOrder').value = sort;
    document.getElementById('makerIsActive').checked = active == 1;

    if (id === 0)
        document.getElementById('makerModalTitle').innerHTML = 'הוספת יצרן';
    else
        document.getElementById('makerModalTitle').innerHTML = 'עריכת יצרן';
}

function closeMakerModal() {
    document.getElementById('makerModal').style.display = 'none';
}

document.getElementById('makerForm').addEventListener('submit', function (e) {

    e.preventDefault();

    let fd = new FormData(this);

    fetch('car_maker_save.php', {
        method: 'POST',
        body: fd
    })
        .then(r => r.json())
        .then(r => {

            if (r.success) {
                location.reload();
            }
            else {
                alert(r.message);
            }

        });

});

function deleteMaker(id) {

    if (!confirm('למחוק את היצרן?'))
        return;

    let fd = new FormData();
    fd.append('id', id);

    fetch('car_maker_delete.php', {

        method: 'POST',
        body: fd

    })
        .then(r => r.json())
        .then(r => {

            if (r.success)
                location.reload();
            else
                alert(r.message);

        });

}