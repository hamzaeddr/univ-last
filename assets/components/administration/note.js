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
    let id_epreuve = false;
    let idEpreuves = [];
    var table_notes_epreuve = $("#datables_notes_epreuve").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/administration/note/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
        url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    function table_note_inscription(){
        var table_notes_inscription =  $("#datatables_notes_inscription").DataTable({
            lengthMenu: [
                [10, 15, 25, 50, 100, 20000000000000],
                [10, 15, 25, 50, 100, "All"],
            ],
            order: [[2, "asc"]],
            ajax: "/administration/note/list/note_inscription/"+id_epreuve,
            processing: true,
            serverSide: true,
            deferRender: true,
            language: {
                url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
            },
            stateSave: true,
            bDestroy: true
        });
    }
    $('body').on('click','#datables_notes_epreuve tbody tr',function () {
        const input = $(this).find("input");
        if(input.is(":checked")){
            input.prop("checked",false);
            const index = idEpreuves.indexOf(input.attr("id"));
            idEpreuves.splice(index,1);
        }else{
            input.prop("checked",true);
            idEpreuves.push(input.attr("id"));
        }
        console.log(idEpreuves);
    })
    $('body').on('dblclick','#datables_notes_epreuve tbody tr',function (e) {
        e.preventDefault();
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_epreuve = null;
        } else {
            $("#datables_notes_epreuve tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            // const icon = $('#note i');
            // icon.removeClass('fa-newspaper').addClass('fa-spinner fa-spin');
            id_epreuve = $(this).attr('id');
            table_note_inscription()
            // icon.addClass('fa-newspaper').removeClass('fa-spinner fa-spin')
        }
    })
    $("#etablissement").select2();
    $("#professeur").select2();
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        table_notes_epreuve.columns().search("");
       
        let response = ""
        if(id_etab != "") {
            if ($("#professeur").val() != "") {
                table_notes_epreuve.columns(6).search($("#professeur").val())
            }
            table_notes_epreuve.columns(0).search(id_etab).draw();
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }else{
            if ($("#professeur").val() != "") {
                table_notes_epreuve.columns(6).search($("#professeur").val()).draw();
            }else{
                table_notes_epreuve.columns().search("").draw();
            }
        }
        $('#semestre').html('').select2();
        $('#module').html('').select2();
        $('#element').html('').select2();
        $('#promotion').html('').select2();
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        table_notes_epreuve.columns().search("");
        if ($("#professeur").val() != "") {
            table_notes_epreuve.columns(6).search($("#professeur").val());
        }
        let response = ""
        if(id_formation != "") {
            table_notes_epreuve.columns(1).search(id_formation).draw();
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }else{
            table_notes_epreuve.columns(0).search($("#etablissement").val()).draw();
        }
        $('#semestre').html('').select2();
        $('#module').html('').select2();
        $('#element').html('').select2();
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id_promotion = $(this).val();
        table_notes_epreuve.columns().search("");
        if ($("#professeur").val() != "") {
            table_notes_epreuve.columns(6).search($("#professeur").val());
        }
        if(id_promotion != "") {
            table_notes_epreuve.columns(2).search(id_promotion).draw();
            const request = await axios.get('/api/semestre/'+id_promotion);
            response = request.data
        }else{
            table_notes_epreuve.columns(1).search($("#formation").val()).draw();
        }
        $('#semestre').html('').select2();
        $('#module').html('').select2();
        $('#element').html('').select2();
        $('#semestre').html(response).select2();
    })
    $("#semestre").on('change', async function (){
        const id_semestre = $(this).val();
        table_notes_epreuve.columns().search("");
        if ($("#professeur").val() != "") {
            table_notes_epreuve.columns(6).search($("#professeur").val())
        }
        if(id_semestre != "") {
            const request = await axios.get('/api/module/'+id_semestre);
            table_notes_epreuve.columns(3).search(id_semestre).draw();
            response = request.data
        }else{
            table_notes_epreuve.columns(2).search($("#promotion").val()).draw();
        }
        $('#module').html('').select2();
        $('#element').html('').select2();
        $('#module').html(response).select2();
    })
    $("#module").on('change', async function (){
        const id_module = $(this).val();
        table_notes_epreuve.columns().search("");
        if ($("#professeur").val() != "") {
            table_notes_epreuve.columns(6).search($("#professeur").val())
        }
        if(id_module != "") {
            table_notes_epreuve.columns(4).search(id_module).draw();
            const request = await axios.get('/api/element/'+id_module);
            response = request.data
        }else{
            table_notes_epreuve.columns(3).search($("#semestre").val()).draw();
        }

        $('#element').html(response).select2();
    })
    $("#element").on('change', async function (){
        const id_element = $(this).val();
        table_notes_epreuve.columns().search("");
        if ($("#professeur").val() != "") {
            table_notes_epreuve.columns(6).search($("#professeur").val())
        }
        table_notes_epreuve.columns(5).search(id_element).draw();
    })
    $("#professeur").on('change', async function (){
        const id_prof = $(this).val();
        table_notes_epreuve.columns(6).search(id_prof).draw();
    })

    $("#note").on('click', async function (e){
        e.preventDefault();
        if(!id_epreuve){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selection une ligne!',
            })
            return;
        }
        $('#notesmodal').modal("show");
    })
    $('body').on('submit','.save_note', async function (e){
        e.preventDefault();
        let id_exgnotes = $(this).find('input').attr('id');
        if( $(this).find('input').val() < 0 || $(this).find('input').val() > 20){
            Toast.fire({
                icon: 'error',
                title: 'La Note doit etre entre 0 et 20',
              })
              return;
        }
        $(this).find('input').blur();
        var formData = new FormData($(this)[0]);
        $(this).parent().parent().next('tr').find('.input_note').focus();
        const request = await axios.post('/administration/note/note_update/'+id_exgnotes, formData);
        response = request.data
        const data = await request.data;
        table_notes_epreuve.ajax.reload(null,false);
    })
    $('body').on('submit','.save_obs', async function (e){
        e.preventDefault();
        $(this).find('input').blur();
        let id_exgnotes = $(this).find('input').attr('id');
        var formData = new FormData($(this)[0]);
        $(this).parent().parent().next('tr').find('.input_obs').focus();
        const request = await axios.post('/administration/note/observation_update/'+id_exgnotes, formData);
        const data = await request.data;
    })
    $('body').on('click','.check_note_ins', async function (){
        var formData = new FormData();
        let id_exgnotes = $(this).attr('id');
        if ($(this).prop('checked') == true) {
            formData.append('absence',true);
            const request = await axios.post('/administration/note/absence_update/'+id_exgnotes, formData);
            const data = await request.data;
            table_notes_epreuve.ajax.reload(null,false);;
        }else{
            formData.append('absence',false);
            const request = await axios.post('/administration/note/absence_update/'+id_exgnotes, formData);
            const data = await request.data;
            table_notes_epreuve.ajax.reload(null,false);;
        }
    })
    $("#import").on('click', async function (e){
        e.preventDefault();
        if(!id_epreuve){
            Toast.fire({
              icon: 'error',
              title: 'Veuillez selection une ligne!',
            })
            return;
        }
        $('#import_en_masse').modal("show");
    })
    $('body').on('click','#epreuve_canvas', function (){
        window.open('/administration/note/canvas/'+id_epreuve, '_blank');
    })
    $("#import_epreuve_save").on("submit", async function(e) {
        e.preventDefault();
        let formData = new FormData($(this)[0]);
        let modalAlert = $("#import_en_masse .modal-body .alert")
    
        modalAlert.remove();
        const icon = $("#epreuve_enregistre i");
        icon.removeClass('fa-check-circle').addClass("fa-spinner fa-spin");
        
        try {
          const request = await axios.post('/administration/note/import/'+id_epreuve, formData);
          const response = request.data;
          $("#import_en_masse .modal-body").prepend(
            `<div class="alert alert-success">
                <p>${response}</p>
              </div>`
          );
          icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
          table_note_inscription()
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
        
        try {
            let formData = new FormData();
            formData.append("epreuves", JSON.stringify(idEpreuves))
            const request = await axios.post('/administration/note/cloturer', formData);
            const response = request.data;
            icon.addClass('fa-lock').removeClass("fa-spinner fa-spin ");
            Toast.fire({
                icon: 'success',
                title: response,
            }) 
            table_notes_epreuve.ajax.reload(null, false)
            idEpreuves = [];
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
            icon.addClass('fa-lock').removeClass("fa-spinner fa-spin ");
            
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
        
        try {
            let formData = new FormData();
            formData.append("epreuves", JSON.stringify(idEpreuves))
            const request = await axios.post('/administration/note/decloturer', formData);
            const response = request.data;
            icon.addClass('fa-lock-open').removeClass("fa-spinner fa-spin ");
            Toast.fire({
                icon: 'success',
                title: response,
            }) 
            table_notes_epreuve.ajax.reload(null, false)
            idEpreuves =  []
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
            icon.addClass('fa-lock-open').removeClass("fa-spinner fa-spin ");
            
        }
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
            const request = await axios.get('/administration/note/checkifanonymat/'+id_epreuve);
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

    $('body').on('click', '#impression_clair', function(e){
        e.preventDefault();
        window.open("/administration/note/impression/"+id_epreuve+"/0", '_blank');
    })
    $('body').on('click', '#impression_anonymat', function(e){
        e.preventDefault();
        window.open("/administration/note/impression/"+id_epreuve+"/1", '_blank');
    })

    $("#capitaliser_etudiant").on('click', async function(e) {
        e.preventDefault();
        if(idEpreuves.length ==0) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez cochez une ou plusieurs ligne!',
            })
            return;
        }
        const icon = $("#capitaliser_etudiant i");
        icon.removeClass('fa-archive').addClass("fa-spinner fa-spin");
        
        try {
            let formData = new FormData();
            formData.append("epreuves", JSON.stringify(idEpreuves))
            const request = await axios.post('/administration/note/capitaliser', formData);
            const response = request.data;
            icon.addClass('fa-archive').removeClass("fa-spinner fa-spin ");
            if(response.count>0) {
                window.open("/"+response.fileName ,"_blank");
            }
            idEpreuves =  []
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
            icon.addClass('fa-archive').removeClass("fa-spinner fa-spin ");
            
        }
    })
    
});