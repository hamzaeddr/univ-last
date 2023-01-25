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

let check;
let inscription_id = null;
    
$(document).ready(function  () {
    $("#valider, #devalider, #simuler").attr('disabled', true)
    const enableButtons = () => {
        if(check == 0) {
            $("#valider").removeClass('btn-secondary').addClass('btn-danger').attr('disabled', false)
            $("#simuler").removeClass('btn-secondary').addClass('btn-primary').attr('disabled', false)
            $("#devalider").addClass('btn-secondary').removeClass('btn-success').attr('disabled', true)
        } else {
            $("#valider").addClass('btn-secondary').removeClass('btn-danger').attr('disabled', true)
            $("#simuler").addClass('btn-secondary').removeClass('btn-primary').attr('disabled', true)
            $("#devalider").removeClass('btn-secondary').addClass('btn-success').attr('disabled', false)
        }
    }
    $("#etablissement").select2();
    $("#etablissement").on('change', async function (){
        const id_etab = $(this).val();
        let response = ""
        if(id_etab != "") {
            const request = await axios.get('/api/formation/'+id_etab);
            response = request.data
        }
        $('#formation').html(response).select2();
    })
    $("#formation").on('change', async function (){
        const id_formation = $(this).val();
        let response = ""
        if(id_formation != "") {
            const request = await axios.get('/api/promotion/'+id_formation);
            response = request.data
        }
        $('#promotion').html(response).select2();
    })
    $("#promotion").on('change', async function (){
        const id_promotion = $(this).val();
        let response = ""
        if(id_promotion != "") {
            const request = await axios.get('/api/semestre/'+id_promotion);
            response = request.data
        }
        $('#semestre').html(response).select2();
    })
    

    $("#get_list_etudiant").on('click', async function(e){
        e.preventDefault();
        $("#list_epreuve_normal").empty()
        let semestre_id = $('#semestre').val();
        if(semestre_id == "" || !semestre_id) {
            Toast.fire({
                icon: 'error',
                title: 'Veuillez selection semestre!',
            })
            return;
        }
        const icon = $("#get_list_etudiant i");
        icon.removeClass('fa-search').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/simulationdeliberation/list/'+semestre_id);
            let response = request.data
            // $("#list_epreuve_normal").DataTable().destroy()
            if ($.fn.DataTable.isDataTable("#list_epreuve_normal")) {
                $('#list_epreuve_normal').DataTable().clear().destroy();
              }
            $("#list_epreuve_normal").html(response.html).DataTable({
                scrollX: true,
                scrollY: true,
                language: {
                    url: "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/French.json",
                },
            });
            check = response.check;
            if(check == 1){
                Toast.fire({
                    icon: 'info',
                    title: "Operation déja valider",
                }) 
            }
            enableButtons();
            icon.addClass('fa-search').removeClass("fa-spinner fa-spin");
        } catch (error) {
            console.log(error)
            icon.addClass('fa-search').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            }) 
        }

    })
    $('body').on('click','#list_epreuve_normal tbody tr',function () {
        if($(this).hasClass('active_databales')) {
            $(this).removeClass('active_databales');
            // $('#inscription-modal').attr('disabled', true);
            inscription_id = null;
        } else {
            $("#list_epreuve_normal tbody tr").removeClass('active_databales');
            $(this).addClass('active_databales');  
            inscription_id = $(this).attr("id")         
        }
    })
   


    
    
    $("#simuler").on("click", () => {  
        $("#note_modal").modal("show")
        getSimulerDetails();
    })
    
    const getSimulerDetails = async () => {
        try {
            const request = await axios.post('/evaluation/simulationdeliberation/simuler/'+inscription_id+"/"+$("#semestre").val());
            let response = request.data
            $("#note_modal .modal-body").html(response);
            // Toast.fire({
            //     icon: 'success',
            //     title: response,
            // });
            setColorRed();
        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    }
    $("body").on("click", ".open h3", function(){
        $(this).parent().find(".elements").slideToggle("slow");
    })
    
    
    $("body").on("keyup change", ".KU3", function () {
        var value = $(this).attr('id');
        // var elementCount = $(this).parent().parent().parent().parent().parent().parent().find(".elements_container");
        var elements = $(this).parent().parent().parent().parent().parent().parent().find(".elements_container");
        var modulex = $(this).parent().parent().parent().parent().parent().parent()
        var modules =  $(this).parent().parent().parent().parent().parent().parent().parent().parent().find(".modules");
        
        var rachat = $(this).parent().parent();
        var cc_rachat =  rachat.find(".cc_rachat").val() 
        var tp_rachat =  rachat.find(".tp_rachat").val() 
        var ef_rachat =  rachat.find(".ef_rachat").val() 
        var element = rachat.find(".element_rachat");
        
        
        var coeff_module =  Number($(this).parent().parent().parent().parent().parent().parent().find(".coefficcient_module").val()) ;
        var coeff_exams =  $(this).parent().parent().parent().parent().parent().find(".coefficcient_element_epreuve").val() ;
        var coefficcient_element_epreuve = coeff_exams.split(" ");
        var coeff_cc =  Number(coefficcient_element_epreuve[0]);
        var coeff_tp =  Number(coefficcient_element_epreuve[1]);
        var coeff_ef =  Number(coefficcient_element_epreuve[2]);
        console.log(elements)

        var calculeNrachatElement = ( ( Number(cc_rachat) * coeff_cc ) + ( Number(tp_rachat) * coeff_tp ) + ( Number(ef_rachat) * coeff_ef ) ) / ( coeff_cc + coeff_tp + coeff_ef );
        
        element.val(Number((calculeNrachatElement).toFixed(2)));
  
  
        var calculeNrachatModule=0;
        var elementSomme=0;
        elements.map(function() {
            // console.log($(this).find(".element_rachat").val(), $(this).find(".coefficcient_element").val());
            calculeNrachatModule += Number($(this).find(".element_rachat").val()) * Number($(this).find(".coefficcient_element").val());
            elementSomme += Number($(this).find(".coefficcient_element").val())
        })
        var  calculeNrachatModulex = calculeNrachatModule / elementSomme ;
        
        
        modulex.find(".module_rachat").val(Number((calculeNrachatModulex).toFixed(2)));
        // console.log(Number(modulex.find(".noteModuleOG").val()) + Number((calculeNrachatModulex).toFixed(2)));
        if((Number(modulex.find(".noteModuleOG").val()) + Number((calculeNrachatModulex).toFixed(2))) > 20){
            // console.log(Number(modulex.find(".noteModuleOG").val()))
            modulex.find(".noteModule").val(Number(modulex.find(".noteModuleOG").val()));
        } else {
            // console.log(Number(modulex.find(".noteModuleOG").val()) + Number((calculeNrachatModulex)))
            modulex.find(".noteModule").val( (Number(modulex.find(".noteModuleOG").val()) + Number((calculeNrachatModulex))).toFixed(2));
        }
        
        
        var calculeNrachatSemestre=0;
        var moduleSomme=0;
        modules.map(function() {
            // console.log($(this).find(".module_rachat").val(), $(this).find(".coefficcient_module").val());
            calculeNrachatSemestre += Number($(this).find(".module_rachat").val()) * Number($(this).find(".coefficcient_module").val());
            moduleSomme += Number($(this).find(".coefficcient_module").val())
        })
       
        var  calculeNrachatSemestrex = Number((calculeNrachatSemestre / moduleSomme).toFixed(2));
  
  
        $('.semestre_rachat').val(Number((calculeNrachatSemestrex).toFixed(2)));
        if(Number($('.semestre_note').val()) + calculeNrachatSemestrex > 20){
        $('.semestre_note').val( Number($('.semestre_noteOG').val()));
        }else{
        $('.semestre_note').val( Number($('.semestre_noteOG').val()) + Number((calculeNrachatSemestrex).toFixed(2)));
        }
  
    });

    const setColorRed = () => {
        $("body .colorRed").map(function() {
            // console.log(this, this.value, $(this), $(this).val())
            if($(this).val() == 1){
                // console.log($(this).parent().parent())
                $(this).parent().find(".titleModule").css("color", "red");
            }
         });
    }

    $("body").on("click", "#save_rachat", async function(e) {
        e.preventDefault();
        const icon = $("#save_rachat i");
        icon.removeClass('fa-check').addClass("fa-spinner fa-spin");
        var data = [];
        var noteSemestreRachat = {
            'id': $(".semestre_rachat").attr("data-id"),
            'note_rachat': $(".semestre_rachat").val(),
        }
        data.push({
                'semestre': noteSemestreRachat
        })
        var noteModules = [];
        var modules = $(".modules");
        modules.map(function(){
            noteModules.push({
                'id': $(this).attr('data-id'),
                'note_rachat': $(this).find(".module_rachat").val()
            })
        })
        data.push({
            'modules': noteModules
        })
        var noteElements = [];
        var elements = $(".elements_container");
        elements.map(function(){
            noteElements.push({
                'id': $(this).attr('data-id'),
                'note_rachat': $(this).find(".element_rachat").val(),
                'cc_rachat': $(this).find(".cc_rachat").val(),
                'tp_rachat': $(this).find(".tp_rachat").val(),
                'ef_rachat': $(this).find(".ef_rachat").val()
            })
        })
        data.push({
            'elements': noteElements
        })
        try {
            var formData = new FormData();
            formData.append("data",  JSON.stringify(data))
            const request = await axios.post('/evaluation/simulationdeliberation/saverachat',formData);
            let response = request.data
            icon.addClass('fa-check').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            });
            $("#note_modal").modal("hide")
        } catch (error) {
            console.log(error)
            icon.addClass('fa-check').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    }) 
    $("#valider").on('click', async function(){
        const icon = $("#valider i");
        icon.removeClass('fa-lock').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/simulationdeliberation/valider');
            let response = request.data
            check = 1;
            enableButtons();
            Toast.fire({
                icon: 'success',
                title: response,
            });
            icon.addClass('fa-lock').removeClass("fa-spinner fa-spin");
        } catch (error) {
            console.log(error)
            icon.addClass('fa-lock').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
    $("#devalider").on('click', async function(){
        const icon = $("#devalider i");
        icon.removeClass('fa-lock-open').addClass("fa-spinner fa-spin");
        try {
            const request = await axios.post('/evaluation/simulationdeliberation/devalider');
            let response = request.data
            check = 0;
            enableButtons();
            icon.addClass('fa-lock-open').removeClass("fa-spinner fa-spin");
            Toast.fire({
                icon: 'success',
                title: response,
            });
        } catch (error) {
            console.log(error)
            icon.addClass('fa-lock-open').removeClass("fa-spinner fa-spin");
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
})


    


