import Image from 'next/image';
import Link from 'next/link';

export default function Navbar() {
  return (
    <nav className="sticky top-0 z-50 w-full border-b border-gray-200 bg-white/80 backdrop-blur-md">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div className="flex items-center gap-2">
          <Link href="/" className="flex items-center gap-2">
            <Image
              src="/images/logo.png"
              alt="Revora Logo"
              width={140}
              height={40}
              className="h-8 w-auto object-contain"
              priority
            />
          </Link>
        </div>

        <div className="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
          <Link href="/" className="hover:text-primary transition-colors">
            Home
          </Link>
          <Link href="#features" className="hover:text-primary transition-colors">
            Features
          </Link>
          <Link href="https://moksedul.dev" className="hover:text-primary transition-colors">
            Contact Us
          </Link>
        </div>

        <div className="flex items-center gap-4">
          <a
            href="/downloads/revora.zip"
            download
            className="rounded-full bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary transition-all"
          >
            Download Plugin
          </a>
        </div>
      </div>
    </nav>
  );
}
