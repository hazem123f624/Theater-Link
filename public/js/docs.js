(function () {
    "use strict";

    const sidebar = document.getElementById("sidebar");
    const backdrop = document.getElementById("sidebarBackdrop");
    const menuButton = document.getElementById("menuButton");
    const closeButton = document.getElementById("sidebarClose");
    const content = document.getElementById("mainContent");
    const searchInput = document.getElementById("apiSearch");
    const searchEmpty = document.getElementById("searchEmpty");
    const backToTop = document.getElementById("backToTop");
    const themeToggle = document.getElementById("themeToggle");

    const getStoredTheme = () => {
        try {
            return window.localStorage.getItem("theaterlink-docs-theme");
        } catch (error) {
            return null;
        }
    };

    const applyTheme = (theme) => {
        const nextTheme = theme === "dark" ? "dark" : "light";

        document.documentElement.dataset.theme = nextTheme;

        if (themeToggle) {
            themeToggle.setAttribute("aria-pressed", nextTheme === "dark" ? "true" : "false");
            themeToggle.setAttribute(
                "aria-label",
                nextTheme === "dark" ? "Switch to light mode" : "Switch to night mode"
            );
        }
    };

    const storedTheme = getStoredTheme();

    applyTheme(storedTheme || document.documentElement.dataset.theme || "light");

    themeToggle?.addEventListener("click", () => {
        const nextTheme = document.documentElement.dataset.theme === "dark" ? "light" : "dark";

        try {
            window.localStorage.setItem("theaterlink-docs-theme", nextTheme);
        } catch (error) {
            console.warn("Unable to save the theme preference", error);
        }

        applyTheme(nextTheme);
    });

    const openSidebar = () => {
        if (!sidebar || !backdrop || !menuButton) {
            return;
        }

        sidebar.classList.add("open");
        backdrop.classList.add("visible");
        menuButton.setAttribute("aria-expanded", "true");
    };

    const closeSidebar = () => {
        if (!sidebar || !backdrop || !menuButton) {
            return;
        }

        sidebar.classList.remove("open");
        backdrop.classList.remove("visible");
        menuButton.setAttribute("aria-expanded", "false");
    };

    const isMobileLayout = () => window.matchMedia("(max-width: 900px)").matches;
    const sidebarCollapsedKey = "theaterlink-docs-sidebar-collapsed";

    const applySidebarCollapsed = (collapsed, persist = false) => {
        document.body.classList.toggle("sidebar-collapsed", collapsed);

        if (menuButton) {
            menuButton.setAttribute("aria-expanded", String(!collapsed));
            menuButton.setAttribute("aria-label", collapsed ? "Open navigation" : "Close navigation");
        }

        if (persist) {
            try {
                window.localStorage.setItem(sidebarCollapsedKey, collapsed ? "1" : "0");
            } catch (error) {
                console.warn("Unable to save the sidebar state", error);
            }
        }
    };

    if (!isMobileLayout()) {
        let collapsed = false;

        try {
            collapsed = window.localStorage.getItem(sidebarCollapsedKey) === "1";
        } catch (error) {
            collapsed = false;
        }

        applySidebarCollapsed(collapsed);
    }

    menuButton?.addEventListener("click", () => {
        if (isMobileLayout()) {
            openSidebar();
            return;
        }

        applySidebarCollapsed(!document.body.classList.contains("sidebar-collapsed"), true);
    });

    closeButton?.addEventListener("click", closeSidebar);
    backdrop?.addEventListener("click", closeSidebar);

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            if (isMobileLayout()) {
                closeSidebar();
            } else if (!document.body.classList.contains("sidebar-collapsed")) {
                applySidebarCollapsed(true, true);
            }
        }
    });

    const sidebarResizer = document.getElementById("sidebarResizer");
    const sidebarWidthKey = "theaterlink-docs-sidebar-width";
    const sidebarMinWidth = 220;
    const sidebarMaxWidth = 560;
    const sidebarDefaultWidth = 292;

    const maxSidebarWidth = () => Math.max(
        sidebarMinWidth,
        Math.min(sidebarMaxWidth, Math.round(window.innerWidth * 0.55))
    );

    const applySidebarWidth = (width, persist = false) => {
        const nextWidth = Math.min(Math.max(width, sidebarMinWidth), maxSidebarWidth());

        document.documentElement.style.setProperty("--docs-sidebar-width", `${nextWidth}px`);
        sidebarResizer?.setAttribute("aria-valuenow", String(nextWidth));
        sidebarResizer?.setAttribute("aria-valuemax", String(maxSidebarWidth()));

        if (persist) {
            try {
                window.localStorage.setItem(sidebarWidthKey, String(nextWidth));
            } catch (error) {
                console.warn("Unable to save the sidebar width", error);
            }
        }

        return nextWidth;
    };

    let storedSidebarWidth = sidebarDefaultWidth;

    try {
        const savedWidth = Number.parseInt(window.localStorage.getItem(sidebarWidthKey) || "", 10);

        if (Number.isFinite(savedWidth)) {
            storedSidebarWidth = savedWidth;
        }
    } catch (error) {
        storedSidebarWidth = sidebarDefaultWidth;
    }

    applySidebarWidth(storedSidebarWidth);

    sidebarResizer?.addEventListener("pointerdown", (event) => {
        if (!sidebar || window.matchMedia("(max-width: 900px)").matches || event.button !== 0) {
            return;
        }

        event.preventDefault();

        const startX = event.clientX;
        const startWidth = sidebar.getBoundingClientRect().width;

        const onMove = (moveEvent) => {
            applySidebarWidth(startWidth + moveEvent.clientX - startX);
        };

        const onEnd = () => {
            document.body.classList.remove("resizing-sidebar");
            sidebarResizer.classList.remove("dragging");
            window.removeEventListener("pointermove", onMove);
            window.removeEventListener("pointerup", onEnd);
            window.removeEventListener("pointercancel", onEnd);

            const currentWidth = Number.parseFloat(
                window.getComputedStyle(document.documentElement).getPropertyValue("--docs-sidebar-width")
            );

            if (Number.isFinite(currentWidth)) {
                applySidebarWidth(currentWidth, true);
            }
        };

        document.body.classList.add("resizing-sidebar");
        sidebarResizer.classList.add("dragging");
        window.addEventListener("pointermove", onMove);
        window.addEventListener("pointerup", onEnd);
        window.addEventListener("pointercancel", onEnd);
    });

    sidebarResizer?.addEventListener("dblclick", () => {
        applySidebarWidth(sidebarDefaultWidth, true);
    });

    sidebarResizer?.addEventListener("keydown", (event) => {
        if (!["ArrowLeft", "ArrowRight", "Home"].includes(event.key)) {
            return;
        }

        event.preventDefault();

        const currentWidth = sidebar?.getBoundingClientRect().width ?? sidebarDefaultWidth;

        if (event.key === "Home") {
            applySidebarWidth(sidebarDefaultWidth, true);
            return;
        }

        applySidebarWidth(currentWidth + (event.key === "ArrowRight" ? 16 : -16), true);
    });

    window.addEventListener("resize", () => {
        if (window.matchMedia("(max-width: 900px)").matches) {
            return;
        }

        const currentWidth = Number.parseFloat(
            window.getComputedStyle(document.documentElement).getPropertyValue("--docs-sidebar-width")
        );

        applySidebarWidth(Number.isFinite(currentWidth) ? currentWidth : sidebarDefaultWidth);
    });

    sidebar?.querySelectorAll("a[href^='#']").forEach((link) => {
        link.addEventListener("click", () => {
            if (window.matchMedia("(max-width: 900px)").matches) {
                closeSidebar();
            }
        });
    });

    // Search and method filters endpoint cards and sidebar links together.
    const methodButtons = Array.from(document.querySelectorAll("[data-method-filter]"));
    let activeMethod = "all";

    const filterEndpoints = () => {
        const term = searchInput ? searchInput.value.trim().toLowerCase() : "";
        const terms = term.split(/\s+/).filter(Boolean);
        const filterActive = term !== "" || activeMethod !== "all";
        const cards = Array.from(document.querySelectorAll(".endpoint-card"));
        const navLinks = Array.from(document.querySelectorAll(".nav-endpoint"));
        const matchesFilter = (element) => {
            const method = (element.dataset.method || "").toLowerCase();
            const haystack = element.dataset.search || "";
            const matchesMethod = activeMethod === "all" || method === activeMethod;
            const matchesSearch = terms.length === 0 || terms.every((word) => haystack.includes(word));

            return matchesMethod && matchesSearch;
        };

        cards.forEach((card) => {
            card.hidden = !matchesFilter(card);
        });

        navLinks.forEach((link) => {
            link.hidden = !matchesFilter(link);
        });

        document.querySelectorAll(".nav-section").forEach((section) => {
            const visibleLinks = section.querySelectorAll(".nav-endpoint:not([hidden])").length;
            section.hidden = filterActive && visibleLinks === 0;

            if (filterActive && visibleLinks > 0) {
                section.open = true;
            }
        });

        document.querySelectorAll(".nav-submodule").forEach((submodule) => {
            const visibleLinks = submodule.querySelectorAll(".nav-endpoint:not([hidden])").length;
            submodule.hidden = filterActive && visibleLinks === 0;

            if (filterActive && visibleLinks > 0) {
                submodule.open = true;
            }
        });

        document.querySelectorAll(".nav-module").forEach((module) => {
            const visibleLinks = module.querySelectorAll(".nav-endpoint:not([hidden])").length;
            module.hidden = filterActive && visibleLinks === 0;

            if (filterActive && visibleLinks > 0) {
                module.open = true;
            }
        });

        document.querySelectorAll(".nav-group").forEach((group) => {
            if (group.querySelector(".nav-endpoint")) {
                const visibleLinks = group.querySelectorAll(".nav-endpoint:not([hidden])").length;
                group.hidden = filterActive && visibleLinks === 0;
            }
        });

        document.querySelectorAll(".endpoint-group").forEach((group) => {
            const visibleCards = group.querySelectorAll(".endpoint-card:not([hidden])").length;
            group.hidden = filterActive && visibleCards === 0;
        });

        document.querySelectorAll(".module-block").forEach((block) => {
            const visibleCards = block.querySelectorAll(".endpoint-card:not([hidden])").length;
            block.hidden = filterActive && visibleCards === 0;
        });

        document.querySelectorAll(".section[id^='consumer-']").forEach((section) => {
            const visibleCards = section.querySelectorAll(".endpoint-card:not([hidden])").length;
            section.hidden = filterActive && visibleCards === 0;
        });

        if (searchEmpty) {
            searchEmpty.hidden = !filterActive || cards.some((card) => !card.hidden);
        }
    };

    methodButtons.forEach((button) => {
        button.addEventListener("click", () => {
            activeMethod = button.dataset.methodFilter || "all";

            methodButtons.forEach((item) => {
                const selected = item === button;
                item.classList.toggle("active", selected);
                item.setAttribute("aria-pressed", selected ? "true" : "false");
            });

            filterEndpoints();
        });
    });

    searchInput?.addEventListener("input", filterEndpoints);

    // Highlight the sidebar link for the endpoint currently in view.
    const navLinks = Array.from(document.querySelectorAll(".nav-link[href^='#']"));
    const targets = navLinks
        .map((link) => {
            const id = link.getAttribute("href").slice(1);
            const element = document.getElementById(id);

            return element ? { link, element } : null;
        })
        .filter(Boolean)
        .sort((a, b) => {
            const position = a.element.compareDocumentPosition(b.element);

            return position & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1;
        });

    let activeLink = null;
    let lockedLink = null;
    let ticking = false;

    const sidebarNav = document.querySelector(".sidebar-nav");

    const openAncestors = (link) => {
        let details = link.closest("details");

        while (details) {
            details.open = true;
            details = details.parentElement?.closest("details") || null;
        }
    };

    /**
     * Keep the sidebar still unless the selected link is actually out of view.
     * The assignment is instant, never a smooth scroll.
     */
    const ensureSidebarLinkVisible = (link) => {
        if (!sidebarNav || !link) {
            return;
        }

        const navRect = sidebarNav.getBoundingClientRect();
        const linkRect = link.getBoundingClientRect();
        const padding = 12;

        if (linkRect.top >= navRect.top + padding && linkRect.bottom <= navRect.bottom - padding) {
            return;
        }

        if (linkRect.top < navRect.top + padding) {
            sidebarNav.scrollTop -= navRect.top + padding - linkRect.top;
        } else {
            sidebarNav.scrollTop += linkRect.bottom - (navRect.bottom - padding);
        }
    };

    const lockActiveLink = (link) => {
        lockedLink = link;
    };

    const releaseActiveLock = () => {
        if (!lockedLink) {
            return;
        }

        lockedLink = null;
        updateActiveLink();
    };

    const setActiveLink = (link, reveal = false) => {
        if (activeLink !== link) {
            activeLink = link;

            navLinks.forEach((item) => {
                const selected = item === link;

                item.classList.toggle("active", selected);

                if (selected) {
                    item.setAttribute("aria-current", "location");
                } else {
                    item.removeAttribute("aria-current");
                }
            });
        }

        // Only a deliberate click / hash navigation reveals the link. The
        // scroll-spy must not open every group it passes on the way.
        if (link && reveal) {
            openAncestors(link);
        }
    };

    const updateActiveLink = () => {
        if (lockedLink || !content || targets.length === 0) {
            return;
        }

        const threshold = content.getBoundingClientRect().top + 150;
        let current = null;

        targets.forEach(({ link, element }) => {
            if (link.hidden || element.hidden) {
                return;
            }

            if (element.getBoundingClientRect().top <= threshold) {
                current = link;
            }
        });

        setActiveLink(current);
    };

    const highlightFromHash = () => {
        const hash = window.location.hash.slice(1);

        if (!hash) {
            lockedLink = null;
            updateActiveLink();

            return false;
        }

        const link = navLinks.find((item) => item.getAttribute("href") === `#${hash}`);

        if (!link) {
            return false;
        }

        lockActiveLink(link);
        setActiveLink(link, true);

        window.requestAnimationFrame(() => {
            ensureSidebarLinkVisible(link);
        });

        return true;
    };

    navLinks.forEach((link) => {
        link.addEventListener("click", (event) => {
            const href = link.getAttribute("href") || "";

            if (!href.startsWith("#")) {
                setActiveLink(link, true);
                return;
            }

            event.preventDefault();

            const target = document.getElementById(href.slice(1));

            // Highlight immediately and keep this link active during the jump.
            lockActiveLink(link);
            setActiveLink(link, true);

            window.requestAnimationFrame(() => {
                ensureSidebarLinkVisible(link);
            });

            if (target) {
                // Jump instantly. A smooth scroll makes the scroll-spy walk
                // through every endpoint between the old and new positions.
                const previousBehavior = content?.style.scrollBehavior ?? "";
                const previousHtmlBehavior = document.documentElement.style.scrollBehavior;

                if (content) {
                    content.style.scrollBehavior = "auto";
                }

                document.documentElement.style.scrollBehavior = "auto";

                target.scrollIntoView({ block: "start" });

                if (content) {
                    content.style.scrollBehavior = previousBehavior;
                }

                document.documentElement.style.scrollBehavior = previousHtmlBehavior;
            }

            if (window.location.hash !== href) {
                window.history.pushState(null, "", href);
            }
        });
    });

    // The locked highlight is released only when the user actually scrolls.
    ["wheel", "touchmove", "pointerdown"].forEach((eventName) => {
        content?.addEventListener(eventName, releaseActiveLock, { passive: true });
    });

    window.addEventListener("keydown", (event) => {
        if (["ArrowUp", "ArrowDown", "PageUp", "PageDown", "Home", "End", " "].includes(event.key)) {
            releaseActiveLock();
        }
    });

    window.addEventListener("hashchange", highlightFromHash);
    window.addEventListener("popstate", highlightFromHash);

    const onScroll = () => {
        if (ticking) {
            return;
        }

        ticking = true;

        window.requestAnimationFrame(() => {
            updateActiveLink();

            const scrolled = Math.max(
                content ? content.scrollTop : 0,
                window.scrollY || document.documentElement.scrollTop
            );

            backToTop?.classList.toggle("visible", scrolled > 420);
            ticking = false;
        });
    };

    content?.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("scroll", onScroll, { passive: true });

    if (!highlightFromHash()) {
        updateActiveLink();
    }

    // Copy buttons for request and response examples.
    document.addEventListener("click", (event) => {
        const button = event.target.closest("[data-copy]");

        if (!button) {
            return;
        }

        const box = button.closest(".code-box");
        const code = box?.querySelector("code");

        if (!code) {
            return;
        }

        const text = code.textContent;

        const copied = () => {
            const original = button.textContent;
            button.textContent = "Copied!";
            button.classList.add("copied");

            window.setTimeout(() => {
                button.textContent = original;
                button.classList.remove("copied");
            }, 1600);
        };

        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(text).then(copied).catch(() => {});
            return;
        }

        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.style.position = "fixed";
        textarea.style.opacity = "0";
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand("copy");
            copied();
        } finally {
            textarea.remove();
        }
    });

    backToTop?.addEventListener("click", () => {
        content?.scrollTo({ top: 0, behavior: "smooth" });
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
})();
