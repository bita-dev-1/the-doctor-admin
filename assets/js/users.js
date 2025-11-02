$(document).ready(function(){
    $('.codexForm').validate({
       rules: {
           'username': {
               required: true
           },
           'first_name': {
               required: true
           },
           'last_name': {
               required: true
           },
           'email': {
               required: true,
               email: true
           },
           'phone': {
               required: true
           },
           'cpassword': {
               required: true,
               equalTo: '#password'
           },
           'password': {
               required: true
           }
       }
    });
});
