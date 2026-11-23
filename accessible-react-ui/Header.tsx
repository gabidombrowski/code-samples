"use client";

import Link from "next/link";
import Image from "next/image";
import { useEffect, useRef, useState } from "react";
import Button from "@/app/components/Button";
import MenuIcon from "@/app/components/MenuIcon";

const NAV_LINKS = [
  { href: "/ai-engineering", label: "AI Engineering" },
  { href: "/how-it-works", label: "How It Works" },
  { href: "/projects", label: "Projects" },
  { href: "/artists", label: "For Artists" },
];

// Mirrors `--breakpoint-nav` in globals.css. The CSS decides which nav is
// visible; this decides when to drop the open panel's state. They have to be
// the same number, so changing one means changing the other.
const NAV_BREAKPOINT = 781;

export default function Header() {
  // Transparent and flush with the page until the user scrolls; only then does
  // the bar earn a background, because only then is there content behind it.
  const [scrolled, setScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const toggleRef = useRef<HTMLButtonElement>(null);
  const headerRef = useRef<HTMLElement>(null);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8);
    onScroll(); // catch a restored scroll position on load
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  // Escape closes and hands focus back to the toggle; a click outside just
  // closes. Both only while the menu is actually open.
  useEffect(() => {
    if (!menuOpen) return;

    const onKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        setMenuOpen(false);
        toggleRef.current?.focus();
      }
    };
    const onPointerDown = (e: PointerEvent) => {
      if (!headerRef.current?.contains(e.target as Node)) setMenuOpen(false);
    };
    // Widening past the breakpoint reveals the desktop nav, so the panel's
    // open state would otherwise linger and reappear on the way back down.
    const desktop = window.matchMedia(`(min-width: ${NAV_BREAKPOINT}px)`);
    const onChange = () => desktop.matches && setMenuOpen(false);

    document.addEventListener("keydown", onKeyDown);
    document.addEventListener("pointerdown", onPointerDown);
    desktop.addEventListener("change", onChange);
    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.removeEventListener("pointerdown", onPointerDown);
      desktop.removeEventListener("change", onChange);
    };
  }, [menuOpen]);

  return (
    <header
      ref={headerRef}
      className={`sticky top-0 z-50 w-full transition-colors duration-300 ${
        scrolled || menuOpen
          ? "bg-diglotus-white/85 backdrop-blur-md border-b border-diglotus-carbon/3"
          : "bg-transparent border-b border-transparent"
      }`}
    >
      <div className="max-w-400 mx-auto w-full px-6 sm:px-12 md:px-22 text-diglotus-carbon">
        <nav aria-label="Main">
          <div
            className={`flex items-center justify-between font-mono gap-8 flex-wrap transition-[padding] duration-300 ${
              scrolled || menuOpen ? "py-3" : "py-18"
            }`}
          >
            {/* Logo */}
            <Link href="/" className="flex items-center gap-4">
              <Image
                src={"/images/brand/digital-lotus-logo.png"}
                alt=""
                aria-hidden="true"
                width={211}
                height={132}
                className={`w-auto transition-[height] duration-300 ${
                  scrolled || menuOpen ? "h-9" : "h-12.5"
                }`}
              />
              <span className="flex items-center gap-2 md:text-2xl lg:text-3xl font-bold text-nowrap">
                Digital Lotus
              </span>
            </Link>

            {/* Desktop Nav */}
            {/* Wraps rather than overflowing — deliberately: at widths (or
                text zoom) where the links and CTA stop fitting beside the
                logo, the wrapped row is preferred over the hamburger all the
                way down to the nav breakpoint. Wrapped rows start at the
                left edge, under the logo, rather than hanging off the right. */}
            <div className="hidden nav:flex items-center gap-5 flex-wrap justify-start">
              <ul className="inline-flex flex-wrap items-center gap-5">
                {NAV_LINKS.map((link) => (
                  <li key={link.href}>
                    <Link href={link.href}>{link.label}</Link>
                  </li>
                ))}
              </ul>

              <Button href="/#contact">Get in touch</Button>
            </div>

            {/* Mobile toggle — the panel below carries the same links as desktop */}
            <button
              ref={toggleRef}
              type="button"
              aria-expanded={menuOpen}
              aria-controls="mobile-menu"
              onClick={() => setMenuOpen((open) => !open)}
              className="nav:hidden flex items-center justify-center w-11 h-11 rounded-xl border border-diglotus-carbon text-diglotus-carbon"
            >
              <MenuIcon open={menuOpen} />
              <span className="sr-only">
                {menuOpen ? "Close menu" : "Open menu"}
              </span>
            </button>
          </div>

          {/* Mobile Nav panel */}
          <div
            id="mobile-menu"
            className={`nav:hidden ${menuOpen ? "block" : "hidden"}`}
          >
            <ul className="flex flex-col gap-1 pb-4">
              {NAV_LINKS.map((link) => (
                <li key={link.href}>
                  <Link
                    href={link.href}
                    onClick={() => setMenuOpen(false)}
                    className="block py-3 text-lg"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>
            <div className="pt-4 pb-10">
              <Button href="/#contact" onClick={() => setMenuOpen(false)}>
                Get in touch
              </Button>
            </div>
          </div>
        </nav>
      </div>
    </header>
  );
}
