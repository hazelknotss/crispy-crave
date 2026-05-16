import MyOrdersBodyClass from "./my-orders-body-class";

export default function MyOrdersLayout({ children }: { children: React.ReactNode }) {
  return <MyOrdersBodyClass>{children}</MyOrdersBodyClass>;
}
