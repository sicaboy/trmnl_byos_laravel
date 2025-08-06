<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout heading="Support" subheading="Support the development of this project">

        <div class="flex items-center gap-4">
            <div class="flex items-center justify-end gap-2">
                <flux:button class="w-42"
                             href="https://github.com/sponsors/bnussbau"
                             target="_blank"
                             icon:trailing="arrow-up-right">{{ __('GitHub Sponsors') }}</flux:button>
                <flux:button class="w-42"
                             href="https://www.buymeacoffee.com/bnussbau"
                             target="_blank"
                             icon:trailing="arrow-up-right">{{ __('Buy me a coffee') }}</flux:button>
            </div>
        </div>

        <div class="relative mt-10">
            <flux:heading>{{ __('Referral Code') }}</flux:heading>
            <flux:subheading>{{ __('Use the code to receive a $15 discount on your TRMNL device purchase.') }}</flux:subheading>

            <div class="mt-3 flex items-center justify-start gap-2">
                <flux:input value="laravel-trmnl" readonly copyable class="max-w-42"/>
                <flux:button class="w-42"
                             href="https://usetrmnl.com/?ref=laravel-trmnl"
                             target="_blank"
                             icon:trailing="arrow-up-right">{{ __('Referral link') }}</flux:button>
            </div>

        </div>
    </x-settings.layout>
</section>
