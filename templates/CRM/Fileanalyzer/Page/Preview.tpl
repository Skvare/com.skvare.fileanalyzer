{* File Details Page Template *}
<div class="crm-container">
    {* File Information Section *}
  <div class="panel file-info-panel">
    <div class="panel-header">
      <h2>{ts}File Details{/ts}</h2>
      <input type="button" onclick="window.close()" class="bckbtn" name="Button" value="Close">
    </div>
    <div class="panel-body">
      <div class="file-overview">
        <div class="file-icon">
          <i class="crm-i {if $fileInfo.is_image}fa-image{elseif $fileInfo.is_pdf}fa-file-pdf-o{else}fa-file-o{/if}"></i>
        </div>
        <div class="file-details">
          <h3>{$fileInfo.filename}</h3>
          <div class="meta-info">
            <span><strong>{ts}Size{/ts}:</strong> {$fileInfo.file_size|filesize}</span>
            <span><strong>{ts}Type{/ts}:</strong> {$fileInfo.mime_type}</span>
            <span><strong>{ts}Directory{/ts}:</strong> {$fileInfo.directory_type}</span>
            <span><strong>{ts}Last Modified{/ts}:</strong> {$fileInfo.modified_date|crmDate}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

    {* Reference Information Section *}
  <div class="panel reference-info-panel">
    <div class="panel-header">
      <h3>{ts}Reference Details{/ts}</h3>
    </div>
    <div class="panel-body">
      <div class="reference-details">
        <div class="reference-type">
          <strong>{ts}Reference Type{/ts}:</strong> {$fileInfo.reference_type}
        </div>
        <div class="reference-location">
          <strong>{ts}Referenced in{/ts}:</strong>
            {if $fileInfo.item_table}{$fileInfo.item_table} (ID: {$fileInfo.item_id}){/if}
        </div>
        <div class="reference-field">
          <strong>{ts}Field Name{/ts}:</strong> {$fileInfo.field_name}
        </div>
      </div>
    </div>
  </div>

    {* Entity Details Section *}
    {if $entityDetails}
      <div class="panel entity-details-panel">
        <div class="panel-header">
          <h3>{ts}Contact Details{/ts}</h3>
        </div>
        <div class="panel-body">
            {* Dynamically display details based on entity type *}
            {if $contactDetails}
              <div class="contact-details">
                <h4><a target="_blank" href="{crmURL p="civicrm/contact/view" q="cid=`$contactDetails.id`&reset=1"}">{$contactDetails.display_name}</a></h4>
                <p><strong>{ts}Type{/ts}:</strong> {$contactDetails.contact_type}</p>
                  {if $contactDetails.email}
                    <p><strong>{ts}Email{/ts}:</strong> {$contactDetails.email}</p>
                  {/if}
              </div>
            {/if}

            {if $fileInfo.item_table == 'civicrm_activity'}
              <div class="activity-details">
                <h4>{ts}Activity Information{/ts}</h4>

                  {* Basic Activity Details *}
                <div class="activity-summary">
                  <p><strong>{ts}Subject{/ts}:</strong> {$entityDetails.subject}</p>
                  <p><strong>{ts}Type{/ts}:</strong>
                      {crmAPI var="activityTypes" entity="OptionValue" action="get" sequential=1 option_group_id="activity_type"}
                      {foreach from=$activityTypes.values item=type}
                          {if $type.value == $entityDetails.activity_type_id}
                              {$type.label}
                          {/if}
                      {/foreach}
                  </p>
                  <p><strong>{ts}Date{/ts}:</strong> {$entityDetails.activity_date_time|crmDate}</p>
                  <p><strong>{ts}Status{/ts}:</strong>
                      {crmAPI var="activityStatuses" entity="OptionValue" action="get" sequential=1 option_group_id="activity_status"}
                      {foreach from=$activityStatuses.values item=status}
                          {if $status.value == $entityDetails.status_id}
                              {$status.label}
                          {/if}
                      {/foreach}
                  </p>
                </div>

                  {* Detailed Activity Contacts *}
                  {if $entityDetails.contacts}
                    <div class="activity-contacts">
                      <h5>{ts}Contacts Involved{/ts}</h5>

                        {* Source Contact(s) *}
                        {if !empty($entityDetails.contacts.source_contact)}
                          <div class="contact-section">
                            <strong>{ts}Source Contact(s){/ts}:</strong>
                            <ul>
                                {foreach from=$entityDetails.contacts.source_contact item=contact}
                                  <li>
                                    <a target="_blank" href="{crmURL p="civicrm/contact/view" q="cid=`$contact.id`&reset=1"}">{$contact.display_name}</a>
                                    ({$contact.contact_type})
                                      {if $contact.email}
                                        - {$contact.email}
                                      {/if}
                                  </li>
                                {/foreach}
                            </ul>
                          </div>
                        {/if}

                        {* Target Contact(s) *}
                        {if !empty($entityDetails.contacts.target_contact)}
                          <div class="contact-section">
                            <strong>{ts}Target Contact(s){/ts}:</strong>
                            <ul>
                                {foreach from=$entityDetails.contacts.target_contact item=contact}
                                  <li>
                                    <a target="_blank" href="{crmURL p="civicrm/contact/view" q="cid=`$contact.id`&reset=1"}">{$contact.display_name}</a>
                                    ({$contact.contact_type})
                                      {if $contact.email}
                                        - {$contact.email}
                                      {/if}
                                  </li>
                                {/foreach}
                            </ul>
                          </div>
                        {/if}

                        {* Assignee Contact(s) *}
                        {if !empty($entityDetails.contacts.assignee_contact)}
                          <div class="contact-section">
                            <strong>{ts}Assignee Contact(s){/ts}:</strong>
                            <ul>
                                {foreach from=$entityDetails.contacts.assignee_contact item=contact}
                                  <li>
                                    <a target="_blank" href="{crmURL p="civicrm/contact/view" q="cid=`$contact.id`&reset=1"}">{$contact.display_name}</a>
                                    ({$contact.contact_type})
                                      {if $contact.email}
                                        - {$contact.email}
                                      {/if}
                                  </li>
                                {/foreach}
                            </ul>
                          </div>
                        {/if}
                    </div>
                  {/if}

                  {* Activity Details (Optional) *}
                  {if $entityDetails.details}
                    <div class="activity-details-text">
                      <h5>{ts}Details{/ts}</h5>
                      <div class="details-content">{$entityDetails.details|purify}</div>
                    </div>
                  {/if}
              </div>
            {elseif $fileInfo.item_table == 'civicrm_contribution'}
              <div class="contribution-details">
                <h4>{ts}Contribution Details{/ts}</h4>
                <p><strong>{ts}Total Amount{/ts}:</strong> {$entityDetails.total_amount|crmMoney}</p>
                <p><strong>{ts}Date{/ts}:</strong> {$entityDetails.receive_date|crmDate}</p>
              </div>
                {* Add more entity type checks as needed *}
            {/if}
        </div>
      </div>
    {/if}
</div>
<script type="text/javascript">
{literal}
// Add keyboard shortcut for closing (Escape key)
document.addEventListener('keydown', function(event) {
  // Check if Escape key is pressed
  if (event.key === 'Escape') {
    window.close()
  }
});
{/literal}
</script>

{* Optional: Add some CSS to style the close button *}
<style type="text/css">
  {literal}
  .close-tab {
    background-color: #6c757d;
    color: white;
    transition: background-color 0.3s ease;
  }

  .close-tab:hover {
    background-color: #495057;
  }

  .close-tab i {
    margin-right: 0.25rem;
  }

  .file-details-container {
    max-width: 800px;
    margin: 0 auto;
  }

  .panel {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 1rem;
  }

  .panel-header {
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e7eb;
  }

  .panel-body {
    padding: 1rem;
  }

  .file-overview {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .file-icon {
    font-size: 3rem;
    color: #6b7280;
  }

  .file-details h3 {
    margin-bottom: 0.5rem;
  }

  .meta-info {
    display: flex;
    gap: 1rem;
    color: #6b7280;
  }

  .action-buttons {
    display: flex;
    gap: 1rem;
  }

  .button.danger {
    background: #dc3545;
    color: white;
  }
  {/literal}
</style>