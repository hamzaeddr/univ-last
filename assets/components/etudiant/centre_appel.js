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
})
$(document).ready(function  () {
    var table = $("#datables_etudiant").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/etudiant/appel/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        responsive: true,
        scrollX: true,
        drawCallback: function () {
            if(id_etudiant) {
            $("body tr#" + id_etudiant).addClass('active_databales');
            }
        },
        preDrawCallback: function(settings) {
            if ($.fn.DataTable.isDataTable('#datables_etudiant')) {
                var dt = $('#datables_etudiant').DataTable();

                //Abort previous ajax request if it is still in process.
                var settings = dt.settings();
                if (settings[0].jqXHR) {
                    settings[0].jqXHR.abort();
                }
            }
        },
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    let id_etudiant = false;
    $('select').select2();

    const getAppelRdv = async () => {
        $('#rdv1').val("");
        $('#rdv2').val("");
        $('#statut_appel').val("");
        $('#Observation').val("");
        const icon = $("#date-d-appel i");
        icon.removeClass('fa-edit').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.get('/etudiant/appel/getAppelRdv_appel/'+id_etudiant);
            const data = request.data;
            $('#rdv1').val(data['rdv1']);
            $('#rdv2').val(data['rdv2']);
            icon.addClass('fa-edit').removeClass("fa-spinner fa-spin");

        } catch (error) {
            // console.log(error.response.data);
        }  
    }

    $('body').on('click','#datables_etudiant tbody tr',function () {
        if($(this).hasClass('active_databales')) {
            id_etudiant = null,
            $('#datables_etudiant tr').removeClass('active_databales');
            return;
        }
        $('#datables_etudiant tr').removeClass('active_databales');
        $(this).addClass('active_databales');
        id_etudiant = $(this).attr('id');
        getAppelRdv()
    })

    $("#date-d-appel").on("click", () => {
        if(!id_etudiant){
            Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
            })
            return;
        }
        $("#date-d-appel-modal").modal("show")
    })

    $("body").on('submit', "#date_appele_save", async (e) => {
    e.preventDefault();
    let formData = new FormData($("#date_appele_save")[0]);
    let modalAlert = $("#date-d-appel-modal .modal-body .alert")
    modalAlert.remove();
    const icon = $("#date_appele_save .btn i");
    icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");

    try {
        const request = await axios.post('/etudiant/appel/rdvappel/'+id_etudiant, formData);
        const response = request.data;
        $("#date-d-appel-modal .modal-body").prepend(
        `<div class="alert alert-success">
            <p>${response}</p>
            </div>`
        );
        document.getElementById("date_appele_save").reset();
        $('select').val("");
        icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        table.ajax.reload(null, false)
    } catch (error) {
        const message = error.response.data;
        // console.log(error, error.response);
        modalAlert.remove();
        $("#date-d-appel-modal .modal-body").prepend(
        `<div class="alert alert-danger">${message}</div>`
        );
        icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
    }
    setTimeout(() => {
        $(".modal-body .alert").remove();
    }, 2500) 

    })
    $('body').on('click','#extraction', function (){
          window.open('/etudiant/appel/extraction_appels', '_blank');
    })
})