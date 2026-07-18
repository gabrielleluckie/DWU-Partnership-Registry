function runFilterEngine() {
    const campusValue = document.getElementById('selectCampus')?.value ?? 'ALL';
    const typeValue = document.getElementById('selectType')?.value ?? 'ALL';
    const statusValue = document.getElementById('selectStatus')?.value ?? 'ALL';

    const rows = document.querySelectorAll('.filterable-row');
    let matchCounter = 0;

    rows.forEach(row => {
        const cPass = campusValue === 'ALL' || row.getAttribute('data-campus') === campusValue;
        const tPass = typeValue === 'ALL' || row.getAttribute('data-type') === typeValue;
        const sPass = statusValue === 'ALL' || row.getAttribute('data-status') === statusValue;

        if (cPass && tPass && sPass) {
            row.style.display = 'table-row';
            matchCounter++;
        } else {
            row.style.display = 'none';
        }
    });

    const emptyNotice = document.getElementById('emptyNoticeRow');
    const hasDataRows = rows.length > 0;

    if (emptyNotice) {
        emptyNotice.style.display = hasDataRows && matchCounter === 0 ? 'table-row' : 'none';
    }

    const resultCount = document.getElementById('dashResultCount');
    if (resultCount) {
        resultCount.textContent = matchCounter + ' record' + (matchCounter === 1 ? '' : 's') + ' shown';
    }
}

function resetDashboardFilters() {
    const campus = document.getElementById('selectCampus');
    const type = document.getElementById('selectType');
    const status = document.getElementById('selectStatus');

    if (campus) campus.value = 'ALL';
    if (type) type.value = 'ALL';
    if (status) status.value = 'ALL';

    runFilterEngine();
}

document.addEventListener('DOMContentLoaded', runFilterEngine);
