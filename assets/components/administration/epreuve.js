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

    let rattrapage = 0;
    let id_epreuve = null;
    let idEpreuves = [];
    let idInscriptions = [];
    
$(document).ready(function  () {
    var tableEpreuveNormal = $("#list_epreuve_normal").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/administration/epreuve/list/normal",
        processing: true,
        serverSide: true,
        deferRender: true,
        drawCallback: function () {
            idEpreuves.forEach((e) => {
                $("body tr#" + e)
                .find("input")
                .prop("checked", true);
            });
            $("body tr#" + id_epreuve).addClass('active_databales')

        },
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
        });
    var tableEpreuveRattrapage = $("#list_epreuve_rattrapage").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/administration/epreuve/list/rattrapage",
        processing: true,
        serverSide: true,
        deferRender: true,
        drawCallback: function () {
            idEpreuves.forEach((e) => {
                $("body tr#" + e)
                .find("input")
                .prop("checked", true);
            });
            $("body tr#" + id_epreuve).addClass('active_databales')

        },
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $('body').on('click','#list_epreuve_normal tbody tr',function () {
        const input = $(this).find("input");
        if(input.is(":checked")){
            input.prop("checked",false);
            const index = idEpreuves.indexOf(input.attr("id"));
            idEpreuves.splice(index,1);
        }else{
            input.prop("checked",true);
            idEpreuves.push(input.attr("id"));
        }
    })
    $('body').on('click','#list_epreuve_rattrapage tbody tr',function () {
        const input = $(this).find("input");
        if(input.is(":checked")){
            input.prop("checked",false);
            const index = idEpreuves.indexOf(input.attr("id"));
            idEpreuves.splice(index,1);
        }else{
            input.prop("checked",true);
            idEpreuves.push(input.attr("id"));
        }
    })
    $('body').on('dblclick','#list_epreuve_normal tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            $('#inscription-modal').attr('disabled', true);

            id_epreuve = null;
        } else {
            $("#list_epreuve_normal tbody tr").removeClass('active_databales');
            $("#list_epreuve_rattrapage tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_epreuve = $(this).attr('id');
           
        }
        
    })
    $('body').on('dblclick','#list_epreuve_rattrapage tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            $('#inscription-modal').attr('disabled', true);

            id_epreuve = null;
        } else {
            $("#list_epreuve_normal tbody tr").removeClass('active_databales');
            $("#list_epreuve_rattrapage tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_epreuve = $(this).attr('id');
           
        }
        
    })
    $('.nav-pills a').on('click', function (e) {
        $(this).tab('show')
        id_epreuve = null;
        idEpreuves = [];
        $("#list_epreuve_normal tbody tr").removeClass('active_databales');
        $("#list_epreuve_rattrapage tbody tr").removeClass('active_databales');
        $("input").prop("checked",false);
        if ($(this).html() == 'Session normal') {
            rattrapage = 0;

        } else {
            rattrapage = 1;
        }   
    
    })
    $("#import_epreuve").on("click", () => {  
        $("#import_en_masse").modal("show")
        $("#import_en_masse .modal-body .alert").remove()
    })
    $("#epreuve_canvas").on('click', function () {
        window.open("/administration/epreuve/canvas", '_blank');
    })

    $("#import_epreuve_save").on("submit", async function(e) {
        e.preventDefault();
        let formData = new FormData($(this)[0]);
        let modalAlert = $("#import_en_masse .modal-body .alert")
    
        modalAlert.remove();
        const icon = $("#epreuve_enregistre i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        
        try {
          const request = await axios.post('/administration/epreuve/import', formData);
          const response = request.data;
          $("#import_en_masse .modal-body").prepend(
            `<div class="alert alert-success">
                <p>${response.message}</p>
              </div>`
          );
          window.open("/"+response.file ,"_blank");
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
          tableEpreuveNormal.ajax.reload(null, false)
          tableEpreuveRattrapage.ajax.reload(null, false)
        } catch (error) {
          const message = error.response.data;
          console.log(error, error.response);
          modalAlert.remove();
          $("#import_en_masse .modal-body").prepend(
            `<div class="alert alert-danger">${message}</div>`
          );
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
          
        }
    })

    $("#affilier_etudiant").on('click' , async function (e) {
        e.preventDefault();
        if(rattrapage === 0) {
            // session normal
            if(idEpreuves.length ==0) {
                Toast.fire({
                    icon: 'error',
                    title: 'Veuillez cochez une ou plusieurs ligne!',
                })
                return;
            }
            const icon = $("#affilier_etudiant i");
            icon.removeClass('fa-link').addClass("fa-spinner fa-spin");
            
            try {
                let formData = new FormData();
                formData.append("epreuves", JSON.stringify(idEpreuves))
                const request = await axios.post('/administration/epreuve/affiliation_normale', formData);
                const response = request.data;
                icon.addClass('fa-link').removeClass("fa-spinner fa-spin ");
                if(response.total > 0) {
                    window.open("/"+response.zipname ,"_blank");
                } else {
                    Toast.fire({
                        icon: 'info',
                        title: "Epreuves déja affilier ou valider",
                    }) 
                }
                tableEpreuveNormal.ajax.reload(null, false)
                tableEpreuveRattrapage.ajax.reload(null, false)
                idEpreuves = [];
            } catch (error) {
                console.log(error)
                const message = error.response.data;
                Toast.fire({
                    icon: 'error',
                    title: message,
                }) 
                icon.addClass('fa-link').removeClass("fa-spinner fa-spin ");
                
            }
        } else {
            if(!id_epreuve) {
                Toast.fire({
                    icon: 'error',
                    title: 'Veuillez selection une ligne!',
                })
                return;
            }
            const icon = $("#affilier_etudiant i");
            icon.removeClass('fa-link').addClass("fa-spinner fa-spin");
            
            
            try {
                const request = await axios.get('/administration/epreuve/etudiants/'+id_epreuve);
                const response = request.data;    
                icon.addClass('fa-link').removeClass("fa-spinner fa-spin ");

                $(".list_etudiants").html(response)
                $(".check_all_etudiant").prop("checked",false);
                $("#affilier_list_etudiant").modal("show");
                $("#affilier_list_etudiant .modal-body .alert").remove();
                
            } catch (error) {
                console.log(error)
                const message = error.response.data;
                Toast.fire({
                    icon: 'error',
                    title: message,
                })
                icon.addClass('fa-link').removeClass("fa-spinner fa-spin ");
                
            }

        }
    })


    $('body').on('click','.check_etudiant',function () {
        const index = idInscriptions.indexOf($(this).val());
        if(index != -1){
            idInscriptions.splice(index,1);
        }else{
            idInscriptions.push($(this).val());
        }
        console.log(idInscriptions);

    })
    $('body').on('click','.check_all_etudiant',function () {
        idInscriptions = [];
        const inscriptions = $(".check_etudiant");
        if($(".check_all_etudiant").prop('checked') == true) {
            inscriptions.prop("checked",true);
            inscriptions.map(function() {
                idInscriptions.push(this.value);
             });
        } else {
            inscriptions.prop("checked",false);
        }
        console.log(idInscriptions);
    })
    $("#cloture_epreuve").on('click', async function(e) {
        e.preventDefault();
        if(idEpreuves.length ==0) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez cochez une ou plusieurs ligne!',
            })
            return;
        }
        const icon = $("#cloture_epreuve i");
        icon.removeClass('fa-lock').addClass("fa-spinner fa-spin");
        let formData = new FormData();
        formData.append("idEpreuves",  JSON.stringify(idEpreuves))
        try {
            const request = await axios.post('/administration/epreuve/cloture', formData);
            const response = request.data;    
            icon.addClass('fa-lock').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            }) 
            idEpreuves = []
            tableEpreuveRattrapage.ajax.reload(null, false);
            tableEpreuveNormal.ajax.reload(null, false);
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            icon.addClass('fa-lock').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'error',
                title: message,
            })
            
        }
    })
    $("#decloturer_epreuve").on('click', async function(e) {
        e.preventDefault();
        if(idEpreuves.length ==0) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez cochez une ou plusieurs ligne!',
            })
            return;
        }
        const icon = $("#decloturer_epreuve i");
        icon.removeClass('fa-lock-open').addClass("fa-spinner fa-spin");
        let formData = new FormData();
        formData.append("idEpreuves",  JSON.stringify(idEpreuves))
        try {
            const request = await axios.post('/administration/epreuve/decloture', formData);
            const response = request.data;    
            icon.addClass('fa-lock-open').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            }) 
            idEpreuves = []
            tableEpreuveRattrapage.ajax.reload(null, false);
            tableEpreuveNormal.ajax.reload(null, false);
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            icon.addClass('fa-lock-open').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'error',
                title: message,
            })
            
        }
    })

    $("#save_list_etudiant").on('click', async function(e) {
        e.preventDefault();
        const icon = $("#save_list_etudiant i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        let modalAlert = $("#affilier_list_etudiant .modal-body .alert")
        modalAlert.remove();
        let formData = new FormData();
        formData.append("idInscriptions", JSON.stringify(idInscriptions))
        formData.append("idEpreuve", id_epreuve)

        try {
            const request = await axios.post('/administration/epreuve/affiliation_rattrapage', formData);
            const response = request.data;    
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
            $("#affilier_list_etudiant .modal-body").prepend(
                `<div class="alert alert-success">
                    <p>${response}</p>
                  </div>`
            );
            $(".list_etudiants").empty()
            tableEpreuveRattrapage.ajax.reload(null, false);
            tableEpreuveNormal.ajax.reload(null, false);
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            modalAlert.remove();
            $("#affilier_list_etudiant .modal-body").prepend(
                `<div class="alert alert-danger">${message}</div>`
            );
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin");
            
        }
    })

    $('select').select2();
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        let response = ""
        if(id_etab != "") {
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }
        $('#element').html('').select2();
        $('#module').html('').select2();
        $('#semestre').html('').select2();
        $('#promotion').html('').select2();
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        let response = ""
        if(id_formation != "") {
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }
        $('#element').html('').select2();
        $('#module').html('').select2();
        $('#semestre').html('').select2();
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id_promotion = $(this).val();
        if(id_promotion != "") {
            const request = await axios.get('/api/semestre/'+id_promotion);
            response = request.data
            const requestt = await axios.get('/api/niv1/'+id_promotion);
            niv1 = requestt.data 
        }
        $('#element').html('').select2();
        $('#module').html('').select2();
        $('#semestre').html(response).select2();
    })
    $("#semestre").on('change', async function (){
        const id_semestre = $(this).val();
        if(id_semestre != "") {
            const request = await axios.get('/api/module/'+id_semestre);
            response = request.data
        }
        $('#element').html('').select2();
        $('#module').html(response).select2();
    })
    $("#module").on('change', async function (){
        const id_module = $(this).val();
        if(id_module != "") {
            const request = await axios.get('/api/element/'+id_module);
            response = request.data
        }
        $('#element').html(response).select2();
    })
    
    $("#ajouter_epreuve").on("click", function(e){  
        e.preventDefault();
        $("#ajouter_epreuve-modal").modal("show")
    })
    $("body").on('submit', "#add_epreuve", async (e) => {
        e.preventDefault();
        // var res = confirm('Vous voulez vraiment ajouter cette enregistrement ?');
        // if(res == 1){
          var formData = new FormData($('#add_epreuve')[0]);
          let modalAlert = $("#ajouter_epreuve-modal .modal-body .alert")
          modalAlert.remove();
          const icon = $("#ajouter_epreuve-modal button i");
          icon.removeClass('fa-check').addClass("fa-spinner fa-spin");
          try {
            const request = await axios.post('/administration/epreuve/add_epreuve', formData);
            const response = request.data;
            $("#ajouter_epreuve-modal .modal-body").prepend(
              `<div class="alert alert-success" style="width: 98%;margin: 0 auto;">
                  <p>${response}</p>
                </div>`
            );
            icon.addClass('fa-check').removeClass("fa-spinner fa-spin ");
            tableEpreuveNormal.ajax.reload(null, false)
            tableEpreuveRattrapage.ajax.reload(null, false)
          }catch (error) {
            const message = error.response.data;
            modalAlert.remove();
            $("#ajouter_epreuve-modal .modal-body").prepend(
              `<div class="alert alert-danger" style="width: 98%;margin: 0 auto;">${message}</div>`
            );
            icon.addClass('fa-check').removeClass("fa-spinner fa-spin ");
          }
        // }
        setTimeout(() => {
          $(".modal-body .alert").remove();
        }, 2500)  
    })
    $("#import_epreuve").on("click", () => {  
        $("#import_en_masse").modal("show")
        $("#import_en_masse .modal-body .alert").remove()
    })
    $('#epreuve_imprimer').on('click', async function(e){
        e.preventDefault();
        if(!id_epreuve) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection une ligne!',
            })
            return;
        }
        const icon = $("#epreuve_imprimer i");
        icon.removeClass('fa-copy').addClass("fa-spinner fa-spin");

        try {
            const request = await axios.get('/administration/epreuve/checkifanonymat/'+id_epreuve);
            const response = request.data;
            console.log(response)
            icon.addClass('fa-copy').removeClass("fa-spinner fa-spin ");
            $("#imprimer_epreuve").modal("show")
            $('#imprimer_epreuve .etudiant_info').html(response.html);
            $('#imprimer_epreuve .epreuve_title').html(response.id);
            if(response.anonymat == "oui") {
                $('#imprimer_epreuve .actions').html(
                    `<a href="#" class="btn btn-success mt-3" id="impression_clair">Impression Clair</a>
                    <a href="#" class="btn btn-secondary mt-3" id="impression_anonymat">Impression Anonymat</a>`
                );
            } else {
                $('#imprimer_epreuve .actions').html(
                    `<a href="#" class="btn btn-success mt-3" id="impression_clair">Impression Clair</a>`
                );
            }

        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
            icon.addClass('fa-copy').removeClass("fa-spinner fa-spin ");

        }
    })
    $('#modifier_epreuve').on('click', async function(e){
        e.preventDefault();
        if(!id_epreuve) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection une ligne!',
            })
            return;
        }
        const icon = $("#modifier_epreuve i");
        icon.removeClass('fa-edit').addClass("fa-spinner fa-spin");

        try {
            const request = await axios.get('/administration/epreuve/edit/'+id_epreuve);
            const response = request.data;
            icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");
            $("#modifier_epreuve-modal").modal("show")
            $("#modifier_epreuve-modal #edit_epreuve").html(response);    
            $('select').select2();     
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
            icon.addClass('fa-edit').removeClass("fa-spinner fa-spin ");

        }
    })
    $('#edit_epreuve').on('submit', async function(e){
        e.preventDefault();
        
        const icon = $("#edit_epreuve button i");
        icon.removeClass('fa-check').addClass("fa-spinner fa-spin");
        let formData = new FormData($(this)[0]);
        try {
            const request = await axios.post('/administration/epreuve/update/'+id_epreuve, formData);
            const response = request.data;
            icon.addClass('fa-check').removeClass("fa-spinner fa-spin ");
            $("#modifier_epreuve-modal").modal("hide")
            tableEpreuveNormal.ajax.reload(null, false)
            tableEpreuveRattrapage.ajax.reload(null, false)
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
            icon.addClass('fa-check').removeClass("fa-spinner fa-spin ");

        }
    })

    $('body').on('click', '#impression_clair', function(e){
        e.preventDefault();
        window.open("/administration/epreuve/impression/"+id_epreuve+"/0", '_blank');
    })
    $('body').on('click', '#impression_anonymat', function(e){
        e.preventDefault();
        window.open("/administration/epreuve/impression/"+id_epreuve+"/1", '_blank');
    })
    $('#capitaliser_etudiant').on('click', async function(e){
        e.preventDefault();
        if(idEpreuves.length == 0) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez cochez une ou plusieurs ligne!',
            })
            return;
        }
        const icon = $("#capitaliser_etudiant i");
        icon.removeClass('fab fa-get-pocket').addClass("fa fa-spinner fa-spin");
        let formData = new FormData();
        formData.append('idEpreuves', JSON.stringify(idEpreuves))
        try {
            const request = await axios.post('/administration/epreuve/capitaliser', formData);
            const response = request.data;
            console.log(response)
            icon.addClass('fab fa-get-pocket').removeClass("fa fa-spinner fa-spin ");
            if(response.count>0) {
                window.open("/"+response.fileName ,"_blank");
            }else {
                Toast.fire({
                    icon: 'info',
                    title: "Epreuves no capitaliser",
                }) 
            }
            idEpreuves =  []
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
            icon.addClass('fab fa-get-pocket').removeClass("fa fa-spinner fa-spin ");
        }
    })
    
})