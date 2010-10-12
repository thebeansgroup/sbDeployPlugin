<script type="text/javascript">
  $(document).ready(function(){

    var executer = {
      step: 0,
      form: '',
      overallSuccess: true,

      run: function(form)
      {
        this.form = form;
        this.execute();
      },

      handleAjax: function(data)
      {
        var success = data.success && executer.overallSuccess;
        executer.overallSuccess = success;

        var successClass = (success) ? 'success' : 'error';

        // give feedback if it's present
        if (data.feedback)
        {
          $('ol#taskList li:last-child').html($('ol#taskList li:last-child').text() +
            '<span class="' + successClass + '">' + data.feedback + '</span>');
        }

        // set the next label if it's present
        if (data.nextLabel)
        {
          $('ol#taskList').append('<li>' + data.nextLabel +
            ' <img src="/images/ajax-loader.gif" id="ajaxLoader"></li>');
        }

        // final feedback if it's there
        if (data.finalMessage)
        {
          $('h2#finalFeedback').addClass(successClass);
          $('h2#finalFeedback').text(data.finalMessage);
        }

        if (data.nextStep)
        {
          executer.step = data.nextStep;
          executer.execute();
        }
      },

      execute: function()
      {
        if (this.step === false)
        {
          return;
        }
        $.getJSON("/frontend_deployment.php/deploy/ajax/" + this.form + "/" + this.step + ".html?staging[repo_uri]=" + $('#staging_repo_uri').val(),
        { previousResult: this.overallSuccess },
        this.handleAjax
      );
      }
    }

    executer.run('<?php echo $formName ?>');
  });
</script>