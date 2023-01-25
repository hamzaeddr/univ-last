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
    })
    let id_bordereau = false;
    var table_borderaux = $("#datables_borderaux").DataTable({
            lengthMenu: [
                [10, 15, 25, 50, 100, 20000000000000],
                [10, 15, 25, 50, 100, "All"],
            ],
            order: [[0, "desc"]],
            ajax: "/facture/bordereau/list",
            processing: true,
            serverSide: true,
            deferRender: true,
            scrollX: true,
            preDrawCallback: function(settings) {
                if ($.fn.DataTable.isDataTable('#datables_borderaux')) {
                    var dt = $('#datables_borderaux').DataTable();
                    //Abort previous ajax request if it is still in process.
                    var settings = dt.settings();
                    if (settings[0].jqXHR) {
                        settings[0].jqXHR.abort();
                    }
                }
            },
            // drawCallback: function () {
                // ids_reglement.forEach((e) => {
                //     $("body tr#" + e)
                //     .find("input")
                //     .prop("checked", true);
                // });
                // $("body tr#" + id_bordereau).addClass('active_databales');
            // },
            language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $("#etablissement").select2();
    $("#paiement").select2();
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        table_borderaux.columns(0).search(id_etab).draw();
    })
    $("#paiement").on('change', async function (){
        const id_paiement = $(this).val();
        table_borderaux.columns(1).search(id_paiement).draw();
    })
    $('body').on('click','#datables_borderaux tbody tr',function (e) {
        e.preventDefault();
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_bordereau = null;
        } else {
            $("#datables_borderaux tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_bordereau = $(this).attr('id');
        }
    })
    $("body").on("click", '#imprimer', function (e) {
        e.preventDefault();
        if(!id_bordereau){
            Toast.fire({
                icon: 'error',
                title: 'Merci de Choisir un bordereau!',
            })
            return;
        }
        window.open('/facture/bordereau/print_borderaux/'+id_bordereau, '_blank');
    });
    
    $("body").on("click", '#supprimer', async function (e) {
        e.preventDefault();
        if(!id_bordereau){
            Toast.fire({
                icon: 'error',
                title: 'Merci de Choisir un bordereau!',
            })
            return;
        }
        Swal.fire({
            position: 'top-end',
            text: "Vous voulez vraiment supprimer cette enregistrement ?",
            icon: 'warning',
            confirmButtonColor: '#d33',
            confirmButtonText: 'Je Confirme'
          }).then(async (result) => {
                if (result.isConfirmed) {
                    const icon = $("#supprimer i");
                    icon.removeClass('fa-trash').addClass("fa-spinner fa-spin");
                    try {
                        const request = await axios.post("/facture/bordereau/supprimer_borderaux/"+id_bordereau);
                        const data = request.data;
                        icon.addClass('fa-trash').removeClass("fa-spinner fa-spin");
                        Toast.fire({
                            icon: 'success',
                            title: 'Borderaux Supprimer',
                        })
                        table_borderaux.ajax.reload(null,false);
                    } catch (error) {
                        const message = error.response.data;
                        icon.addClass('fa-trash').removeClass("fa-spinner fa-spin");
                        Toast.fire({
                            icon: 'error',
                            title: 'Some Error',
                        })
                    }
                }
          })
    });
    $('body').on('click','#extraction', function (){
        if(!id_bordereau){
            Toast.fire({
                icon: 'error',
                title: 'Merci de Choisir un bordereau!',
            })
            return;
        }
      window.open('/facture/bordereau/extraction_borderaux/'+id_bordereau, '_blank');
    })
})