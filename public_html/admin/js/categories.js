function openCategoryModal(parentId = null, id = '', name = '', sort = 0, active = 1) {

    document.getElementById('catParentId').value = parentId ?? '';
    document.getElementById('catId').value = id;
    document.getElementById('catName').value = name;
    document.getElementById('catSortOrder').value = sort;
    document.getElementById('catIsActive').checked = active == 1;

    document.getElementById('modalTitle').innerHTML =
        id ? 'עריכת קטגוריה' :
            (parentId ? 'הוספת תת קטגוריה' : 'הוספת קטגוריה ראשית');

    document.getElementById('categoryModal').style.display = 'flex';
}

function closeCategoryModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

document.getElementById('categoryForm').addEventListener('submit', function (e) {

    e.preventDefault();

    fetch('category_save.php', {
        method: 'POST',
        body: new FormData(this)
    })
        .then(r => r.json())
        .then(res => {

            if (res.success) {

                location.reload();

            } else {

                document.getElementById('categoryMsg').innerHTML = res.message;

            }

        });

});