/**
 *
 * You can write your JS code here, DO NOT touch the default style file
 * because it will make it harder for you to update.
 *
 */
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

 $(document).ready(function(){
    $(".password_change").on("click", function(){
        $("#password_modal").modal("show")
    })
    $("#password_modal #save_password_change").on("submit", async function(e) {
        e.preventDefault();
        var formData = new FormData($(this)[0])
        const icon = $("#password_modal #save_password_change i");

        try {
            icon.remove('fa-check-circle').addClass("fa-spinner fa-spin ");
            const request = await axios.post('/passwordchange', formData);
            const response = request.data;
            // icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
            // $('#password_modal #save')[0].reset();
            // $("#password_modal").modal("hide")
            window.location.href="/logout";
        } catch (error) {
            console.log(error, error.response);
            const message = error.response.data;
            Toast.fire({
                icon: 'error',
                title: message,
              })
            icon.addClass('fa-check-circle').removeClass("fa-spinner fa-spin ");
            
        }
    })
})