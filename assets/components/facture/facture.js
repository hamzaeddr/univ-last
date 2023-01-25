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
    let id_facture = false;
    var table_facture = $("#datables_facture").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/facture/factures/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        scrollX: true,
        drawCallback: function () {
                $("body tr#" + id_facture).addClass('active_databales');
        },
        preDrawCallback: function(settings) {
            if ($.fn.DataTable.isDataTable('#datables_facture')) {
                var dt = $('#datables_facture').DataTable();

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
    // $("#banque").select2();
    // $("#paiement").select2();
    // $("#etablissement").select2();
    // $("#modifier_org-modal #org").select2();
    // $("#organisme").select2();
    // $("#reglement").select2();
    $("select").select2();
    
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        table_facture.columns(1).search("");
        let response = ""
        if(id_etab != "") {
            if ($("#reglement") && $("#reglement").val() != "") {
                table_facture.columns(2).search($("#reglement").val())
            }
            if ($("#organisme").val() != "") {
                table_facture.columns(3).search($("#organisme").val())
            }
            table_facture.columns(0).search(id_etab).draw();
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }else{
            table_facture.columns(0).search(id_etab).draw();
            if ($("#reglement") && $("#reglement").val() != "") {
                table_facture.columns(2).search($("#reglement").val())
            }
            if ($("#organisme").val() != "") {
                table_facture.columns(3).search($("#organisme").val())
            }
        }
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        table_facture.columns().search("");
        if ($("#reglement") && $("#reglement").val() != "") {
            table_facture.columns(2).search($("#reglement").val())
        }
        if ($("#organisme").val() != "") {
            table_facture.columns(3).search($("#organisme").val());
        }
        let response = ""
        if(id_formation != "") {
            table_facture.columns(1).search(id_formation).draw();
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }else{
            table_facture.columns(0).search($("#etablissement").val()).draw();
        }
    })
    $("#reglement").on('change', async function (){
        const id_reglement = $(this).val();
        table_facture.columns(2).search(id_reglement).draw();
    })
    $("#organisme").on('change', async function (){
        const id_organisme = $(this).val();
        table_facture.columns(3).search(id_organisme).draw();
    })
    let reglement = false;
    const getMontant = () => {
        axios.get('/facture/factures/getMontant/'+id_facture)
        .then(success => {
            reglement = null;
            $("#ajouter").removeClass('btn-primary').addClass('btn-secondary').attr('disabled', false);
            if (success.data != 'vide') {
                reglement = 12;
                // $("#montant").val(success.data['montant']);
                // $("#montant2").val(success.data['montant']);
                $("#montant_facture").val(success.data['montant_facture']);
                $("#ajouter").removeClass('btn-secondary').addClass('btn-primary').attr('disabled', true);
            }
        })
        .catch(err => {
            console.log(err)
        })
    }
    const getFacture = () => {
        const icon = $("#facture i");
        icon.removeClass('fa-money-bill-alt').addClass("fa-spinner fa-spin");
        axios.get('/facture/factures/detaille_facture/'+id_facture)
        .then(success => {
            if(success.data[0] == 0){
                $('.modal-facture #add_detaille').css('display','none');
                $('.modal-facture #add').css('display','none');
                $('.modal-facture #detaille_active').css('display','none');
            }else{
                $('.modal-facture #add_detaille').css('display','block');
                $('.modal-facture #add').css('display','flex');
                $('.modal-facture #detaille_active').css('display','block');
            }
            $('.table_detaille_facture tbody').html(success.data[1])
            icon.removeClass('fa-spinner fa-spin').addClass("fa-money-bill-alt");
            // console.log(success.data[0]);
        })
        .catch(err => {
            console.log(err)
        })
    }
    const getOrganismeByFacture = async () => {
        if(id_facture){
            const request = await axios.get('/api/organisme/'+id_facture);
            response = request.data
            $('#org').html(response).select2();
        }    
    }
    
    const load_frais_preins = () => {
        if(id_facture){
            axios.get('/facture/factures/article_frais/'+id_facture)
            .then(success => {
                $('#detail_facture_modal #frais').html(success.data).select2();
                $('#detail_facture_modal #montantt').val('');
            })
            .catch(err => {
                console.log(err);
            })
        }    
    }
    $('body').on('click','#datables_facture tbody tr',function (e) {
        e.preventDefault();
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_facture = null;
            reglement = null;
            $("#ajouter").removeClass('btn-primary').addClass('btn-secondary').attr('disabled', false);
        } else {
            $("#datables_facture tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_facture = $(this).attr('id');
            console.log(id_facture);
            getOrganismeByFacture()
            getMontant();
            getFacture();
            load_frais_preins();
        }
    })
    
    $('body').on('click','#facture',function (e) {
        e.preventDefault();
        if(!id_facture){
            Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
            })
            return;
        }
        $("#detail_facture_modal").modal('show');
    });
    extra_frais
    $('input[type=radio][name=organ]').on('change', async function (e){
        e.preventDefault();
        if (this.value == 0) {
            const request = await axios.get('/api/getorganismepasPayant');
            response = request.data
            $('.select-organ #org').html(response).select2();
            $('.select-organ').css('display','block');
        }else{
            $('.select-organ #org').html("");
            $('.select-organ').css('display','none');
        }
    })
    $('body').on('change','.modal-facture #frais',function (e) {
        e.preventDefault();
        let frais = $(this).find(':selected').attr('data-id');
        if(frais != ""){
            $('.modal-facture #montantt').val();
        }
        $('.modal-facture #montantt').val(frais);
    });
    $('body').on('click','#add_detaille',async function (e) {
        e.preventDefault();
        const icon = $(this).find('i');
        icon.removeClass('fa-plus').addClass("fa-spinner fa-spin");
        let organisme_id  = $('.select-organ #org').val();
        if ($("input[name='organ']:checked").val() == 1) {
            organisme_id = 7
        }
        
        let formData = new FormData();
        formData.append('frais', $('#frais').val());
        formData.append('montant', $('#montantt').val());
        formData.append('ice', $('#ice').val());
        formData.append('organisme_id', organisme_id);

        let modalAlert =  $(".modal-facture .modal-body .alert");
        modalAlert.remove();
        try{
            const request = await axios.post('/facture/factures/add_detaille/'+id_facture,formData)
            getFacture();            
            $(".modal-facture .modal-body").prepend(
                `<div class="alert alert-success">Facture Bien Ajouter</div>`
            );
            $('select').val('');
            icon.removeClass('fa-spinner fa-spin').addClass("fa-plus");
            getMontant();
            table_facture.ajax.reload(null, false);
        }catch(error){
            const message = error.response.data;            
            $(".modal-facture .modal-body").prepend(
                `<div class="alert alert-danger">${message}</div>`
            );
            icon.removeClass('fa-spinner fa-spin').addClass("fa-plus");
        }
        setTimeout(() => {
            $(".modal-facture .modal-body .alert").remove();
        }, 4000);
    });
    ////////////////////delete all
        $('body').on('click','#delete_detaille',async function (e) {
            e.preventDefault();
            // alert(id_facture)
            // let id_det = $(this).attr('id');
            
            var res = confirm('Vous voulez vraiment desactiver tout les frais ?');
            if(res == 1){
                const icon = $(this).find('i');
                icon.removeClass('fa-window-close').addClass("fa-spinner fa-spin");
                try{
                    const request = await  axios.post('/facture/factures/cloture_all_detaille/'+id_facture)
                    getMontant()
                    getFacture();
                    table_facture.ajax.reload(null, false);
                    icon.removeClass('fa-spinner fa-spin').addClass("fa-window-close");
                }catch(error){
                    const message = error.response.data;
                    icon.removeClass('fa-spinner fa-spin').addClass("fa-window-close");
                }
            }
        });
    ////////////////////delete all
    $('body').on('click','.detaille_cloture',async function (e) {
        e.preventDefault();
        const icon = $(this).find('i');
        icon.removeClass('fa-window-close').addClass("fa-spinner fa-spin");
        let id_det = $(this).attr('id');
        try{
            const request = await  axios.post('/facture/factures/cloture_detaille/'+id_det)
            getMontant()
            getFacture();
            table_facture.ajax.reload(null, false);
            icon.removeClass('fa-spinner fa-spin').addClass("fa-window-close");
        }catch(error){
            const message = error.response.data;
            icon.removeClass('fa-spinner fa-spin').addClass("fa-window-close");
        }
    });
    $('body').on('click','#ajouter',function (e) {
        e.preventDefault();
        if(!id_facture){
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection une ligne!',
            })
            return;
        }
        if(!reglement){
            Toast.fire({
                icon: 'error',
                title: 'Cette Facture N\'a Aucun Detail!',
            })
            return;
        }
        $("#ajouter_modal").modal('show');
    });
    
    $("body").on("submit", '.new_facture-form', async function (e) {
        e.preventDefault();
        let formdata = $(this).serialize()
        let modalAlert =  $("#ajouter_modal .modal-body .alert");
        modalAlert.remove();
        const icon = $(".new_facture-form .btn i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        try{
            const request = await  axios.post('/facture/factures/ajouter_reglement/'+id_facture,formdata)
            const data = request.data;
            $("#ajouter_modal .modal-body").prepend(
                `<div class="alert alert-success">Reglement Bien Ajouter</div>`
            ); 
            $(this).trigger("reset");
            $('.new_facture-form select').val('').trigger("change");
            getMontant();
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
            reglement = false;
            table_facture.ajax.reload(null, false);
            window.open('/facture/factures/facture/'+id_facture+'/'+data, '_blank');
        }catch(error){
            const message = error.response.data;
            console.log(error, error.response);
            modalAlert.remove();
            $("#ajouter_modal .modal-body").prepend(
                `<div class="alert alert-danger">${message}</div>`
            );
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        }
        setTimeout(() => {
           $("#ajouter_modal .modal-body .alert").remove();
        }, 4000);
    });
    $('body').on('click','#modifier',async function (e) {
        e.preventDefault();
        // if(!id_facture){
        //     Toast.fire({
        //     icon: 'error',
        //     title: 'Veuillez selection une ligne!',
        //     })
        //     return;
        // }
        // $("#modifier_org-modal").modal('show');
    });
    
    $('body').on('click','#modifier_org', async function(e){
        e.preventDefault();
        let modalAlert =  $("#modifier_org-modal .modal-body .alert");
        modalAlert.remove();
        const icon = $(".modal_modifier_org-facture .btn i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        let formData = new FormData();
        formData.append('organisme', $('#org').val());
        try{
            const request = await axios.post('/facture/factures/modifier_organisme_facture/'+id_facture,formData)
            const data = request.data;
            $("#modifier_org-modal .modal-body").prepend(
                `<div class="alert alert-success">${data}</div>`
            ); 
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
            table_facture.ajax.reload(null, false);
            $('#org').select2()
        }catch(error){
            const message = error.response.data;
            modalAlert.remove();
            $("#modifier_org-modal .modal-body").prepend(
                `<div class="alert alert-danger">${message}</div>`
            );
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
        }
        setTimeout(() => {
           $("#modifier_org-modal .modal-body .alert").remove();
        }, 4000);

    })
    $("body").on("click", '#imprimer', async function (e) {
        e.preventDefault();
        if(!id_facture){
            Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
            })
            return;
        }
        window.open('/facture/factures/printfacture/'+id_facture, '_blank');
    });
  
    $('body').on('click','#extraction', function (){
      window.open('/facture/factures/extraction_factures', '_blank');
    })
    
    $('body').on('click','#extra_frais',function (e) {
        e.preventDefault();
        $("#annee_extraction_frais").modal('show');
    });
    $('body').on('click','#export_frais',function (e) {
        e.preventDefault();
        let annee = $('#annee').val();
        // alert(annee);
        window.open('/facture/factures/extraction_factures_by_annee/'+annee, '_blank');
    });
});