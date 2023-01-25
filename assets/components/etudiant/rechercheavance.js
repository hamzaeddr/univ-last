
$(document).ready(function  () {
    $('.nav-pills a').on('click', function (e) {
        $(this).tab('show')
    })
    var typingTimer;                //timer identifier
    var doneTypingInterval = 1000;
    let id_admission = null;
    $("#etudiant").select2({
        minimumInputLength: 3,  // required enter 3 characters or more
        allowClear: true,
        placeholder: 'Etudiant',
        language: "fr",
        ajax: {
           dataType: 'json',
           url: '/etudiant/rechercheavance/find',  
        //    delay: 5,  // ini bebas mau di pake atau tidak
           data: function(params) {
             return {
               search: params.term
             }
           },
           processResults: function (data, page) {
            var dataArray = data.map(function (item) {
                return {
                    text: item.code +" "+item.nom + " " +item.prenom,
                    id: item.id
                }
            })
            return {
                results: dataArray
            };
         },
       }
    })
    
   

    $("body").on("change", "#etudiant", async function(){
        
        try {
            const request = await axios.post('/etudiant/rechercheavance/findAnnee/'+$(this).val());
            let response = request.data
            $("#annee").html(response).select2();

        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
    $("body").on("click", "#recherche", async function(){
        
        try {
            const request = await axios.post('/etudiant/rechercheavance/recherche/'+$("#annee").val());
            let response = request.data
            $(".information").html(response.informations);
            $("#administratif").html(response.administratif);
            $("#academique").html(response.academique);


        } catch (error) {
            console.log(error)
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
            });
        }
    })
})