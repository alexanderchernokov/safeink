{* Smarty Template for SD Contact Form plugin.
   Last updated: 2015-01-31 *}
{if $contact_form_paragraph|count_characters > 0}
<div class="contact_header">
  {$contact_form_paragraph}
</div>
{/if}
<div id="contactForm">
	<div class="loader"></div>
        <div id="p6_usermessage">{$errors_arr}</div>
        <form id="p6_contact_form" class="" action="#" method="post">
            {$secure_token}
                   {* Honeypot to protect agains spammers. Do not remove *}
                    {$honeypot}
                    {* End Honeypot *}
                    
                    {foreach from=$formfields item=field}
                       <p>
                            <label for="{$field.formattedname}">{$field.displayname}</label>
                            {$field.input}
                       </p>
                    {/foreach}
                   {$captcha}
               </div>
                <input class="submit" type="submit" name="submit" id='contact-form-submit' value="{$language.send_message}" />
      </form>
</div>
       
