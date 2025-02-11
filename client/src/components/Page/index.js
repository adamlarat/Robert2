import './index.scss';
import { APP_NAME } from '@/globals/constants';
import Loading from '@/components/Loading';
import ErrorMessage from '@/components/ErrorMessage';

// @vue/component
export default {
    name: 'Page',
    props: {
        name: { type: String, required: true },
        title: { type: String, default: null },
        help: { type: String, default: undefined },
        error: { type: [String, Error], default: null },
        isLoading: Boolean,
        actions: { type: Array, default: undefined },
        render: { type: Function, default: undefined },
    },
    watch: {
        title(newTitle) {
            this.updateTitle(newTitle);
        },
    },
    mounted() {
        this.updateTitle(this.title);
    },
    beforeDestroy() {
        this.$store.commit('setPageRawTitle', null);
    },
    methods: {
        updateTitle(newTitle) {
            this.$store.commit('setPageRawTitle', newTitle ?? null);
            document.title = [newTitle, APP_NAME].filter(Boolean).join(' - ');
        },
    },
    render() {
        const { help, actions, error, isLoading, render } = this.$props;
        const content = render ? render() : this.$slots.default;

        const renderHelp = () => {
            if (!isLoading && !error && !help) {
                return null;
            }

            return (
                <div class="header-page__help">
                    {isLoading && <Loading horizontal />}
                    {!isLoading && error && <ErrorMessage error={error} />}
                    {!isLoading && !error && help}
                </div>
            );
        };

        return (
            <div class="content">
                <div class="content__header header-page">
                    {renderHelp()}
                    {actions && actions.length > 0 && (
                        <nav class="header-page__actions">
                            {actions}
                        </nav>
                    )}
                </div>
                <div class="content__main-view">
                    <div class={['Page', `Page--${this.name}`]}>
                        {content}
                    </div>
                </div>
            </div>
        );
    },
};
