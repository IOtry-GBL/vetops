@component('mail::message')
# Medication Reminder

Hello {{ $user->full_name }},

This is a reminder that your pet's medication is due!

**Medication Details:**
- **Name:** {{ $medication_name }}
- **Dosage:** {{ $dosage }}
- **Scheduled Time:** {{ $scheduled_at->format('M d, Y h:i A') }}
- **Confirmation Deadline:** {{ $deadline->format('M d, Y h:i A') }}

Please confirm that you have administered the medication to your pet within the next 3 hours through your pet care portal.

@component('mail::button', ['url' => route('pet-owner.dashboard')])
Go to Portal
@endcomponent

If you have any questions or concerns, please contact your veterinarian.

Thanks,<br>
{{ config('app.name') }}

@component('mail::subcopy')
This is an automated reminder from your veterinary clinic. Please do not reply to this email.
@endcomponent
@endcomponent
