import { ArrowRight, Download } from 'lucide-react';

export default function Hero() {
  return (
    <section className="relative overflow-hidden bg-white py-12">
      <div className="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
        
        <div className="mx-auto max-w-3xl mb-8 flex justify-center">
            <div className="inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/5 px-3 py-1 text-sm font-medium text-primary">
                <span className="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-primary opacity-75"></span>
                <span className="relative inline-flex rounded-full h-2 w-2 bg-primary"></span>
                v1.0.0 Now Available
            </div>
        </div>

        <h1 className="mx-auto max-w-4xl text-5xl font-bold tracking-tight text-zinc-900 sm:text-7xl">
          The Smartest <br className="hidden sm:block" />
          <span className="text-primary">
            Review System
          </span>{' '}
          for WordPress
        </h1>

        <p className="mx-auto mt-6 max-w-2xl text-lg leading-8 text-zinc-600">
          Boost credibility with professional, responsive, and customizable reviews. 
          Elementor-ready, schema-optimized, and built for performance.
        </p>

        <div className="mt-10 flex items-center justify-center gap-x-6">
          <a
            href="/downloads/revora.zip"
            download
            className="group flex items-center gap-2 rounded-full bg-primary px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-primary/30 hover:bg-primary/90 hover:shadow-primary/50 transition-all transform hover:-translate-y-1"
          >
            <Download className="h-4 w-4" />
            Download Plugin
          </a>
          <a
            href="#features"
            className="flex items-center gap-2 text-sm font-semibold leading-6 text-zinc-900 hover:text-primary transition-colors"
          >
            View Features <ArrowRight className="h-4 w-4" />
          </a>
        </div>

      </div>
    </section>
  );
}
