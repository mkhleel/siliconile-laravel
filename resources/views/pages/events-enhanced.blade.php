<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Modules\Cms\Models\Post;
use Modules\Cms\Models\Category;

new
#[Layout('layouts.app')]
#[Title('Siliconile | Events & Workshops')]
class extends Component
{
    public string $selectedCategory = '';

    public function with(): array
    {
        // Get events from CMS posts - assuming 'event' category or status
        $query = Post::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '>=', now())
            ->orderBy('published_at', 'asc');

        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }

        $upcomingEvents = $query->take(6)->get();

        $pastEvents = Post::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<', now())
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get();

        $categories = Category::query()
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return [
            'upcomingEvents' => $upcomingEvents,
            'pastEvents' => $pastEvents,
            'categories' => $categories,
        ];
    }
};

?>

<main class="flex-1">
    <x-sections.hero
        title="{{ __('Events & <span class=') }}"text-primary'>Workshops</span>"
        subtitle="{{ __('Join our vibrant community events, workshops, and networking sessions. Connect with fellow entrepreneurs, learn from experts, and grow your startup.') }}"
    />

    <!-- Upcoming Events -->
    <x-sections.content title="{{ __('Upcoming Events') }}" subtitle="Don't miss these exciting opportunities to learn, network, and grow your startup">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
            @forelse($upcomingEvents as $event)
                <x-cards.event-card
                    :title="$event->title"
                    :description="$event->excerpt"
                    :category="$event->category?->name"
                    :date="$event->published_at?->format('F j, Y')"
                    :image="$event->featured_image ? asset('storage/' . $event->featured_image) : null"
                    action="Learn More"
                    :actionUrl="route('events.show', $event->slug)"
                />
            @empty
                <!-- Sample events if none in database -->
                <x-cards.event-card
                    title="{{ __('Startup Pitch Night') }}"
                    description="{{ __('Monthly pitch event where startups present to investors and get feedback from the community.') }}"
                    category="Networking"
                    date="January 25, 2025"
                    time="6:00 PM - 9:00 PM"
                    location="Siliconile Hub, Luxor"
                    action="Register"
                    :actionUrl="route('contact')"
                />

                <x-cards.event-card
                    title="{{ __('Fundraising Masterclass') }}"
                    description="{{ __('Learn from successful founders and VCs about raising capital, crafting pitch decks, and investor relations.') }}"
                    category="Workshop"
                    date="February 2, 2025"
                    time="2:00 PM - 5:00 PM"
                    location="Online + Hybrid"
                    action="Register"
                    :actionUrl="route('contact')"
                />

                <x-cards.event-card
                    title="{{ __('Tech Talk: AI in Business') }}"
                    description="{{ __('Explore how artificial intelligence is transforming businesses and learn practical applications for your startup.') }}"
                    category="Tech Talk"
                    date="February 15, 2025"
                    time="7:00 PM - 8:30 PM"
                    location="Siliconile Hub, Luxor"
                    action="Register"
                    :actionUrl="route('contact')"
                />

                <x-cards.event-card
                    title="{{ __('Women in Tech Meetup') }}"
                    description="{{ __('A networking event celebrating and supporting women entrepreneurs and tech professionals in Egypt.') }}"
                    category="Networking"
                    date="February 20, 2025"
                    time="5:00 PM - 7:00 PM"
                    location="Siliconile Hub, Luxor"
                    action="Register"
                    :actionUrl="route('contact')"
                />

                <x-cards.event-card
                    title="{{ __('Marketing on a Budget Workshop') }}"
                    description="{{ __('Learn growth hacking techniques and cost-effective marketing strategies for early-stage startups.') }}"
                    category="Workshop"
                    date="March 1, 2025"
                    time="10:00 AM - 1:00 PM"
                    location="Siliconile Hub, Luxor"
                    action="Register"
                    :actionUrl="route('contact')"
                />

                <x-cards.event-card
                    title="{{ __('Demo Day 2025') }}"
                    description="{{ __('Watch our incubated startups pitch to investors and celebrate their achievements after months of hard work.') }}"
                    category="Demo Day"
                    date="March 15, 2025"
                    time="4:00 PM - 8:00 PM"
                    location="Siliconile Hub, Luxor"
                    action="Request Invite"
                    :actionUrl="route('contact')"
                />
            @endforelse
        </div>
    </x-sections.content>

    <!-- Event Categories -->
    <x-sections.content :muted="true" title="{{ __('Event Categories') }}" subtitle="{{ __('We host a variety of events to support your entrepreneurial journey') }}">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <x-ui.card :hover="true">
                <div class="text-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
                        <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold">{{ __('Networking Events') }}</h3>
                    <p class="text-sm text-muted-foreground">{{ __('Connect with entrepreneurs, investors, and industry experts.') }}</p>
                </div>
            </x-ui.card>

            <x-ui.card :hover="true">
                <div class="text-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
                        <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold">Workshops</h3>
                    <p class="text-sm text-muted-foreground">{{ __('Hands-on learning sessions on business and technical skills.') }}</p>
                </div>
            </x-ui.card>

            <x-ui.card :hover="true">
                <div class="text-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
                        <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold">{{ __('Tech Talks') }}</h3>
                    <p class="text-sm text-muted-foreground">{{ __('Learn about the latest technologies and industry trends.') }}</p>
                </div>
            </x-ui.card>

            <x-ui.card :hover="true">
                <div class="text-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto">
                        <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                        </svg>
                    </div>
                    <h3 class="font-semibold">{{ __('Pitch Events') }}</h3>
                    <p class="text-sm text-muted-foreground">{{ __('Present your startup to investors and get valuable feedback.') }}</p>
                </div>
            </x-ui.card>
        </div>
    </x-sections.content>

    <!-- Past Events / Highlights -->
    @if($pastEvents->count() > 0)
    <x-sections.content title="{{ __('Past Events') }}" subtitle="{{ __('Highlights from our previous events') }}">
        <div class="grid gap-8 md:grid-cols-3">
            @foreach($pastEvents as $event)
                <x-cards.event-card
                    :title="$event->title"
                    :description="$event->excerpt"
                    :category="$event->category?->name"
                    :date="$event->published_at?->format('F j, Y')"
                    :image="$event->featured_image ? asset('storage/' . $event->featured_image) : null"
                />
            @endforeach
        </div>
    </x-sections.content>
    @endif

    <!-- Newsletter Section -->
    <section class="py-20 bg-primary text-primary-foreground">
        <div class="container px-4 md:px-6">
            <div class="max-w-2xl mx-auto text-center space-y-6">
                <h2 class="text-3xl md:text-4xl font-bold">{{ __('Never Miss an Event') }}</h2>
                <p class="text-lg opacity-90">{{ __('{{ __('Subscribe') }} to our newsletter to get notified about upcoming events, workshops, and networking opportunities.') }}</p>
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                    @csrf
                    <input
                        type="email"
                        name="email"
                        placeholder="{{ __('Enter your email') }}"
                        required
                        class="flex h-12 w-full rounded-md border border-primary-foreground/20 bg-primary-foreground/10 px-4 py-2 text-sm placeholder:text-primary-foreground/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-foreground"
                    />
                    <button type="submit" class="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium h-12 px-6 bg-primary-foreground text-primary hover:bg-primary-foreground/90 transition-colors">
                        Subscribe
                    </button>
                </form>
            </div>
        </div>
    </section>

    <x-sections.cta
        title="{{ __('Want to Host an Event?') }}"
        subtitle="{{ __('We offer our space for community events, meetups, and workshops. Get in touch to discuss hosting your next event at Siliconile.') }}"
        primaryAction="Contact Us"
        :primaryUrl="route('contact')"
        secondaryAction="View Spaces"
        :secondaryUrl="route('spaces')"
    />
</main>
