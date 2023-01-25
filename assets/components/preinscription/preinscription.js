$(document).ready(function () {
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    },
});
var table_preins = $("#datables_preinscription").DataTable({
    lengthMenu: [
        [10, 15, 25, 50, 100, 20000000000000],
        [10, 15, 25, 50, 100, "All"],
    ],
    order: [[0, "desc"]],
    ajax: "/preinscription/preinscriptions/list",
    processing: true,
    serverSide: true,
    deferRender: true,
    language: {
    url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
    },
});




})

