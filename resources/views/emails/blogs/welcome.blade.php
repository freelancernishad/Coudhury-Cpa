<x-mail::message>
<div style="text-align: center; margin-bottom: 25px;">
    <h2 style="color: #0835A8; margin-bottom: 5px; font-size: 24px;">Chaudri CPA</h2>
    <p style="color: #646464; font-size: 14px; margin-top: 0;">Your Trusted Financial Partner</p>
</div>

# Welcome to our Newsletter!

Hi there,

Thank you for joining the **Chaudri CPA** community. You've successfully subscribed to our blog newsletter. 

From now on, you'll be the first to receive:
*   **Expert Tax Strategies** to optimize your finances.
*   **Business Growth Insights** from industry professionals.
*   **Important Updates** regarding financial regulations.

<x-mail::button :url="'https://chaudricpa.com/blog'">
Explore Our Latest Articles
</x-mail::button>

We're excited to have you on board! If you have any specific topics you'd like us to cover, feel free to reach out to our support team.

Best regards,<br>
**The Chaudri CPA Team**

<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; text-align: center;">
    <p style="font-size: 12px; color: #a0aec0;">
        © {{ date('Y') }} Chaudri CPA. All rights reserved.
    </p>
</div>

<hr style="border: none; border-top: 1px solid #edf2f7; margin: 30px 0;">

<div style="text-align: center;">
    <p style="font-size: 11px; color: #cbd5e0;">
        If you didn't mean to subscribe, you can safely <a href="{{ $unsubscribeUrl }}" style="color: #0835A8; text-decoration: underline;">unsubscribe here</a>.
    </p>
</div>
</x-mail::message>
