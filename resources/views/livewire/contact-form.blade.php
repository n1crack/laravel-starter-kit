<form wire:submit="submit" class="space-y-4">
    @if ($sent)
        <div class="rounded-md border border-green-600/30 bg-green-600/10 px-4 py-3 text-sm text-green-700 dark:text-green-400">
            Thanks! Your message has been sent.
        </div>
    @endif

    <div>
        <label for="contact-name" class="text-sm font-medium">Name</label>
        <input
            id="contact-name"
            type="text"
            wire:model="name"
            class="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
        />
        @error('name')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact-email" class="text-sm font-medium">Email</label>
        <input
            id="contact-email"
            type="email"
            wire:model="email"
            class="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
        />
        @error('email')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="contact-message" class="text-sm font-medium">Message</label>
        <textarea
            id="contact-message"
            rows="4"
            wire:model="message"
            class="border-border bg-background mt-1 w-full rounded-md border px-3 py-2 text-sm"
        ></textarea>
        @error('message')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <button
        type="submit"
        class="bg-foreground text-background w-full rounded-md px-4 py-2 text-sm font-medium hover:opacity-90"
        wire:loading.attr="disabled"
    >
        <span wire:loading.remove>Send message</span>
        <span wire:loading>Sending...</span>
    </button>
</form>
