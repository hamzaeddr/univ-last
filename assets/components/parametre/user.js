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
    let id_user = false;
    let idInscription = [];
    let frais = [];
    
    $(document).ready(function  () {
    const checkInputIfAllChildAreChecked = () => {
        // console.log($(".sousModules"));
        $(".sousModules").map(function() {
            // console.log($(this).parent().find('.inputOperation').not(':checked'));
            if($(this).parent().find('.inputOperation').not(':checked').length === 0) {
                $(this).find(".inputSousModule").prop("checked", true);
            }
        })
       
        $(".modules").map(function() {
            if($(this).parent().find('.inputSousModule').not(':checked').length === 0) {
                $(this).find(".inputModule").prop("checked", true);
            }
        })

        // console.log($('.modules').find(".inputModule").not(':checked'))
        if($('.modules').find(".inputModule").not(':checked').length === 0) {
            $('.tous').prop("checked", true);
        }
        
    }
   
    var table = $("#datatables_gestion_users").DataTable({
        lengthMenu: [
            [10, 15, 25, 50, 100, 20000000000000],
            [10, 15, 25, 50, 100, "All"],
        ],
        order: [[0, "desc"]],
        ajax: "/parametre/user/list",
        processing: true,
        serverSide: true,
        deferRender: true,
        language: {
            url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
        },
    });
    $('body').on('click','#datatables_gestion_users tbody tr',function () {
        // const input = $(this).find("input");
        
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            id_user = null;
        } else {
            $("#datatables_gestion_users tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');
            id_user = $(this).attr('id');
        }
        
    })
    $("#privillege").on("click", async (e) => {
        e.preventDefault();
        $(".privilege input:checkbox").prop("checked", false);
        const icon = $("#privillege i");
        if(!id_user){
          Toast.fire({
            icon: 'error',
            title: 'Veuillez selection une ligne!',
          })
          return;
        }
        try {
            icon.remove('fa-plus').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/parametre/user/getoperations/'+id_user);
            const response = request.data;
            response.map(element => {
                console.log(element);
                $(".buttons ."+element.id).prop("checked", true);
            })
            icon.addClass('fa-plus').removeClass("fa-spinner fa-spin ");
            checkInputIfAllChildAreChecked();
            $("#privillege-modal").modal("show")
        } catch (error) {
            const message = error.response.data;
            console.log(error, error.response);
           
            icon.addClass('fa-plus').removeClass("fa-spinner fa-spin ");
            
        }
    })
    
    $(".Collapsable").on("click", function () {

        $(this).parent().children().toggle();
        $(this).toggle();
    
    });
    $(".inputSousModule").on('click', async function (){
        let url;
        let sous_module = $(this).attr("data-module");
        if($(this).is(":checked")){
            $(this).parent().parent().find(".inputOperation").prop("checked", true);
            url = "/parametre/user/sousmodule/"+sous_module+"/"+id_user+"/add";
        }else{
            $(this).parent().parent().find(".inputOperation").prop("checked", false);
            url = "/parametre/user/sousmodule/"+sous_module+"/"+id_user+"/remove";

        }
        checkInputIfAllChildAreChecked()
        try {
            const request = await axios.post(url);
            const response = request.data;
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
    $(".inputModule").on('click', async function (){
        let url;
        let module = $(this).attr("data-id");
        if($(this).is(":checked")){
            $(this).parent().parent().find("input:checkbox").prop("checked", true);
            url = "/parametre/user/module/"+module+"/"+id_user+"/add";
        }else{
            $(this).parent().parent().find("input:checkbox").prop("checked", false);
            url = "/parametre/user/module/"+module+"/"+id_user+"/remove";

        }
        checkInputIfAllChildAreChecked();
        try {
            const request = await axios.post(url);
            const response = request.data;
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
    $(".inputOperation").on('click', async function (){
        let url;
        let operation = $(this).attr("data-operation");
        if($(this).is(":checked")){
            // $(".inputOperation").parent().parent().find("input:checkbox").prop("checked", true);
            url = "/parametre/user/operation/"+operation+"/"+id_user+"/add";
        }else{
            // $(".inputOperation").parent().parent().find("input:checkbox").prop("checked", false);
            url = "/parametre/user/operation/"+operation+"/"+id_user+"/remove";

        }
        checkInputIfAllChildAreChecked();
        try {
            const request = await axios.post(url);
            const response = request.data;
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
    $(".tous").on('click', async function (){
        let url;
        if($(this).is(":checked")){
            $(".tous").parent().parent().find("input:checkbox").prop("checked", true);
            url = "/parametre/user/all/"+id_user+"/add";
        }else{
            $(".tous").parent().parent().find("input:checkbox").prop("checked", false);
            // $(".inputOperation").parent().parent().find("input:checkbox").prop("checked", false);
            url = "/parametre/user/all/"+id_user+"/remove";
        }
        checkInputIfAllChildAreChecked();
        try {
            const request = await axios.post(url);
            const response = request.data;
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })

    $("body").on("click", ".disable",async function(){
        var id = $(this).attr("id");
        try {
            const request = await axios.post("/parametre/user/active/"+id+"/0");
            const response = request.data;
            table.ajax.reload();
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
    $("body").on("click", ".enable", async function(){
        var id = $(this).attr("id");
        try {
            const request = await axios.post("/parametre/user/active/"+id+"/1");
            const response = request.data;
            table.ajax.reload();
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
    
    $("body").on("click", ".btn_reinitialiser",async function(){
        var id = $(this).attr("id");
        try {
            const request = await axios.post("/reinitialiser/"+id);
            const response = request.data;
            table.ajax.reload();
            Toast.fire({
                icon: 'success',
                title: response,
            }) 
        } catch (error) {
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }
    })
})


