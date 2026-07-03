<div id="categoryModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h2 id="modalTitle">הוספת קטגוריה</h2>

        <form id="categoryForm">
            <input type="hidden" name="id" id="catId">
            <input type="hidden" name="parent_id" id="catParentId">

            <label>שם קטגוריה</label>
            <input type="text" name="name" id="catName" required>

            <label>סדר תצוגה</label>
            <input type="number" name="sort_order" id="catSortOrder" value="0">

            <label>
                <input type="checkbox" name="is_active" id="catIsActive" value="1" checked>
                פעיל
            </label>

            <div class="modal-actions">
                <button type="submit" class="btn btn-green">שמור</button>
                <button type="button" class="btn btn-red" onclick="closeCategoryModal()">ביטול</button>
            </div>
        </form>

        <div id="categoryMsg"></div>
    </div>
</div>