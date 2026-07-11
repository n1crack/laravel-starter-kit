<x-layouts.public>
    <section class="mx-auto max-w-6xl px-6 py-24 text-center">
        <h1 class="mx-auto max-w-2xl text-4xl font-semibold tracking-tight text-balance sm:text-5xl">
            Build your next idea with a solid foundation
        </h1>
        <p class="text-muted-foreground mx-auto mt-6 max-w-xl text-lg text-balance">
            Public pages powered by Blade & Livewire. Admin and user panels powered by Inertia & React.
        </p>
        <div class="mt-10 flex items-center justify-center gap-4">
            <a
                href="{{ route('register') }}"
                class="bg-foreground text-background rounded-md px-6 py-2.5 font-medium hover:opacity-90"
            >
                Get started
            </a>
            <a href="{{ route('login') }}" class="border-border rounded-md border px-6 py-2.5 font-medium">
                Log in
            </a>
        </div>
    </section>

    <section class="mx-auto max-w-xl px-6 pb-24">
        <h2 class="text-center text-2xl font-semibold">Contact us</h2>
        <p class="text-muted-foreground mt-2 text-center text-sm">
            This form is a Livewire component — no page reload needed.
        </p>
        <div class="mt-8">
            <livewire:contact-form />
        </div>
    </section>
</x-layouts.public>
