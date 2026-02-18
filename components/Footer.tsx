import Image from 'next/image';
import Link from 'next/link';

export default function Footer() {
  return (
    <footer className="bg-white border-t border-zinc-200">
      <div className="mx-auto max-w-7xl px-6 py-12 flex flex-col items-center justify-center lg:px-8">
        <div className="flex items-center justify-center mb-4">
            <Link href="/">
                <Image
                src="/images/logo.png"
                alt="Revora"
                width={100}
                height={32}
                className="h-8 w-auto mix-blend-multiply"
                />
            </Link>
        </div>
        <p className="text-center text-xs leading-5 text-zinc-500">
            &copy; {new Date().getFullYear()} Revora Plugin, Inc. All rights reserved.
        </p>
      </div>
    </footer>
  );
}
