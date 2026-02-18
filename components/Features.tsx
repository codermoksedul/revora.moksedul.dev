import { Code, LayoutGrid, Palette, ShieldCheck, Star, Zap } from 'lucide-react';

const features = [
  {
    name: 'Advanced Review Slider',
    description: 'Showcase your best reviews in a beautiful, responsive slider. Fully customizable with Elementor widgets.',
    icon: Star,
  },
  {
    name: 'Modern Grid & List Layouts',
    description: 'Display reviews in a clean list or a responsive grid. Choose from Classic, Modern, or Boxed card styles.',
    icon: LayoutGrid,
  },
  {
    name: 'Smart Moderation System',
    description: 'Full control over your reviews. Approve, reject, or hold reviews directly from the dashboard.',
    icon: ShieldCheck,
  },
  {
    name: 'AJAX Submission & Loading',
    description: 'Provide a seamless experience. Users can submit reviews and load more content without ever refreshing the page.',
    icon: Zap,
  },
  {
    name: 'Customizable Design',
    description: 'Tailor every pixel. Control colors, typography, spacing, and shadows directly from Elementor or plugin settings.',
    icon: Palette,
  },
  {
    name: 'Schema.org SEO Rich Snippets',
    description: 'Dominate search results. Built-in JSON-LD Schema markup helps search engines understand your reviews.',
    icon: Code,
  },
];

export default function Features() {
  return (
    <section id="features" className="py-24 sm:py-32 bg-zinc-50">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mx-auto max-w-2xl text-center">
          <h2 className="text-base font-semibold leading-7 text-indigo-600">Everything you need</h2>
          <p className="mt-2 text-3xl font-bold tracking-tight text-zinc-900 sm:text-4xl">
            Powerful Features for <br /> Reviews
          </p>
          <p className="mt-6 text-lg leading-8 text-zinc-600">
             Revora is built to be the only review plugin you'll ever need. From collection to display, we've got you covered.
          </p>
        </div>

        <div className="mx-auto mt-16 max-w-7xl sm:mt-20 lg:mt-24">
          <dl className="grid grid-cols-1 gap-x-8 gap-y-8 lg:grid-cols-3">
            {features.map((feature) => (
              <div 
                key={feature.name} 
                className="relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-8 shadow-sm transition-all hover:shadow-md hover:border-indigo-200 group"
              >
                <dt className="flex items-center gap-x-3 text-lg font-semibold leading-7 text-zinc-900">
                  <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600 group-hover:bg-indigo-500 transition-colors">
                    <feature.icon className="h-6 w-6 text-white" aria-hidden="true" />
                  </div>
                  {feature.name}
                </dt>
                <dd className="mt-4 flex flex-auto flex-col text-base leading-7 text-zinc-600">
                  <p className="flex-auto">{feature.description}</p>
                </dd>
              </div>
            ))}
          </dl>
        </div>
      </div>
    </section>
  );
}
